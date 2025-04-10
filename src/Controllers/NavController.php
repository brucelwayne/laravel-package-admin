<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Mallria\App\Models\LinkModel;
use Mallria\Category\Models\TransCategoryModel;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\ErrorJsonResponse;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Core\Models\PageModel;
use Mallria\Main\Enums\CacheKey;
use Mallria\Main\Enums\LinkType;
use Mallria\Main\Models\MainNavModel;
use Mallria\Shop\Models\TransInsightModel;
use Mallria\Shop\Models\TransProductModel;

class NavController extends BaseAdminController
{
    function index(Request $request)
    {
        $keywords = $request->get('q');
        if (!empty($keywords)) {
            $navs = MainNavModel::search($keywords)
                ->paginate(10);
            if (!is_empty($navs)) {
                foreach ($navs as $nav) {
                    $ancestors = $nav->getAncestors();
                    $nav->setAttribute('ancestors', $ancestors);
                    $path = collect($ancestors)->pluck('name');
                    $nav->setAttribute('path', $path);
                }
            }
            return InertiaAdminFacade::render('Admin/Nav/SearchResult', [
                'navs' => $navs,
            ]);
        }

        $navs = MainNavModel::with(['model', 'model.translations', 'translations', 'navParent', 'navParent.model', 'navParent.translations',])
            ->orderBy('id', 'desc')
            ->defaultOrder()
            ->get()
            ->toTree();

        return InertiaAdminFacade::render('Admin/Nav/Index', [
            'navs' => $navs,
        ]);
    }

    function search(Request $request)
    {
        $query = urldecode($request->get('q', ''));
        $perPage = $request->get('per_page', 10);

        if (empty($query)) {
            $navs = MainNavModel::orderBy('id', 'desc')->paginate($perPage);
        } else {
            $navs = MainNavModel::search($query)->orderBy('created_at', 'desc')->paginate($perPage);
        }

        if (!is_empty($navs)) {
            foreach ($navs as $nav) {
                $ancestors = $nav->getAncestors();
                $nav->setAttribute('ancestors', $ancestors);
                $path = collect($ancestors)->pluck('name');
                $nav->setAttribute('path', $path);
            }
        }

        return new  SuccessJsonResponse([
            'navs' => $navs,
        ]);
    }

    function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent' => ['nullable'],
            'name' => ['required', 'string', 'max:32'],
            'link_type' => ['required', 'in:none,link,page,category,product,insight'],
            'link_target' => ['required', 'in:_blank,_self'],
            'href' => ['nullable', 'url'],
            'link' => ['nullable', 'max:32'],
            'page' => ['nullable', 'max:32'],
            'category' => ['nullable', 'max:32'],
            'product' => ['nullable', 'max:32'],
            'insight' => ['nullable', 'max:32'],

            'icon_type' => ['nullable', 'in:svg,image'],
            // 条件字段
            'icon_svg' => ['required_if:icon_type,svg', 'nullable', 'string'],
            'icon_image' => ['required_if:icon_type,image', 'nullable', 'url'],
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first(), [
                'errors' => $validator->errors(),
            ]);
        }

        $validated = $validator->validated();

        $parent = null;
        $parent_hash = Arr::get($validated, 'parent');
        if (!empty($parent_hash)) {
            $parent = MainNavModel::byHash($parent_hash);
        }

        $link_type = Arr::get($validated, 'link_type');
        $link_type = LinkType::from($link_type);

        $model = null;
        if ($link_type === LinkType::None) {

        } else if ($link_type === LinkType::Link) {
            $link_hash = Arr::get($validated, 'link');
            $href = Arr::get($validated, 'href');
            if (empty($href)) {
                return new ErrorJsonResponse(__('链接地址不能为空！'));
            }
            if (!empty($link_hash)) {
                $model = LinkModel::byHashOrFail($link_hash);
                $model->update(['href', $href]);
            } else {
                $model = LinkModel::create([
                    'href' => $href,
                ]);
            }
        } else if ($link_type == LinkType::Page) {

            $model_hash = Arr::get($validated, 'page');
            if (empty($model_hash)) {
                return new ErrorJsonResponse(__('请选择关联的对象！'));
            }
            $model = PageModel::byHashOrFail($model_hash);

        } elseif ($link_type === LinkType::Category) {

            $model_hash = Arr::get($validated, 'category');
            if (empty($model_hash)) {
                return new ErrorJsonResponse(__('请选择关联的对象！'));
            }
            $model = TransCategoryModel::byHashOrFail($model_hash);

        } elseif ($link_type === LinkType::Product) {
            if (empty($model_hash)) {
                return new ErrorJsonResponse(__('请选择关联的对象！'));
            }
            $model_hash = Arr::get($validated, 'product');
            $model = TransProductModel::byHashOrFail($model_hash);
        } elseif ($link_type === LinkType::Insight) {

            $model_hash = Arr::get($validated, 'insight');
            if (empty($model_hash)) {
                return new ErrorJsonResponse(__('请选择关联的对象！'));
            }
            $model = TransInsightModel::byHashOrFail($model_hash);

        }

        if ($link_type !== LinkType::None && empty($model)) {
            return new ErrorJsonResponse('关联模型错误！');
        }

        $main_nav_model = MainNavModel::create([
            'parent_id' => empty($parent) ? null : $parent->getKey(),
            'name' => trim(Arr::get($validated, 'name')),
            'link_type' => $link_type,
            'link_target' => Arr::get($validated, 'link_target'),
            'icon_type' => Arr::get($validated, 'icon_type'),
            'icon_svg' => Arr::get($validated, 'icon_svg'),
            'icon_image' => Arr::get($validated, 'icon_image'),
        ]);

        if ($link_type !== LinkType::None) {
            $main_nav_model->model()->associate($model);
            $main_nav_model->save();
        }

        if (!empty($parent)) {
            $parent->appendNode($main_nav_model);
        }

        return new SuccessJsonResponse([
            'nav' => $main_nav_model,
        ]);
    }

    function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nav' => ['required', 'string', 'max:32'],
            'parent' => ['nullable'],
            'name' => ['required', 'string', 'max:32'],
            'link_type' => ['required', 'in:none,link,page,category,product,insight'],
            'link_target' => ['required', 'in:_blank,_self'],
            'href' => ['nullable', 'url'],
            'link' => ['nullable', 'max:32'],
            'page' => ['nullable', 'max:32'],
            'category' => ['nullable', 'max:32'],
            'product' => ['nullable', 'max:32'],

            'icon_type' => ['nullable', 'in:svg,image'],
            // 条件字段
            'icon_svg' => ['required_if:icon_type,svg', 'nullable', 'string'],
            'icon_image' => ['required_if:icon_type,image', 'nullable', 'url'],
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first(), [
                'errors' => $validator->errors(),
            ]);
        }

        $validated = $validator->validated();

        $parent = null;
        $parent_hash = Arr::get($validated, 'parent');
        if (!empty($parent_hash)) {
            $parent = MainNavModel::byHash($parent_hash);
        }

        $link_type = Arr::get($validated, 'link_type');
        $link_type = LinkType::from($link_type);

        $model = null;
        if ($link_type === LinkType::None) {

        } else if ($link_type === LinkType::Link) {
            $link_hash = Arr::get($validated, 'link');
            $href = Arr::get($validated, 'href');
            if (empty($href)) {
                return new ErrorJsonResponse(__('链接地址不能为空！'));
            }
            if (!empty($link_hash)) {
                $model = LinkModel::byHashOrFail($link_hash);
                $model->update(['href' => $href]);
            } else {
                $model = LinkModel::create([
                    'href' => $href,
                ]);
            }
        } else if ($link_type == LinkType::Page) {
            $model_hash = Arr::get($validated, 'page');
            $model = PageModel::byHashOrFail($model_hash);
        } elseif ($link_type === LinkType::Category) {
            $model_hash = Arr::get($validated, 'category');
            $model = TransCategoryModel::byHashOrFail($model_hash);
        } elseif ($link_type === LinkType::Product) {
            $model_hash = Arr::get($validated, 'product');
            $model = TransProductModel::byHashOrFail($model_hash);
        } elseif ($link_type === LinkType::Insight) {
            $model_hash = Arr::get($validated, 'insight');
            $model = TransInsightModel::byHashOrFail($model_hash);
        }

        $main_nav_hash = Arr::get($validated, 'nav');
        $main_nav_model = MainNavModel::byHashOrFail($main_nav_hash);
        $main_nav_model->update([
            'parent_id' => empty($parent) ? null : $parent->getKey(),
            'name' => trim(Arr::get($validated, 'name')),
            'link_type' => $link_type,
            'link_target' => Arr::get($validated, 'link_target'),
            'icon_type' => Arr::get($validated, 'icon_type'),
            'icon_svg' => Arr::get($validated, 'icon_svg'),
            'icon_image' => Arr::get($validated, 'icon_image'),
        ]);

        if (!empty($model)) {
            $main_nav_model->model()->associate($model);
            $main_nav_model->save();
        } else {
            $main_nav_model->update([
                'model_id' => null,
                'model_type' => null,
            ]);
        }

        if (!empty($parent) && $parent->getKey() !== $main_nav_model->parent_id) {
            $parent->appendNode($main_nav_model);
        }

        return new SuccessJsonResponse([
            'nav' => $main_nav_model,
        ]);
    }

    function up(Request $request)
    {
        $category_hash = $request->post('category');
        $category_model = MainNavModel::byHashOrFail($category_hash);
        $result = $category_model->up();
        return new SuccessJsonResponse([
            'result' => $result
        ]);
    }

    function down(Request $request)
    {
        $category_hash = $request->post('category');
        $category_model = MainNavModel::byHashOrFail($category_hash);
        $result = $category_model->down();
        return new SuccessJsonResponse([
            'result' => $result
        ]);
    }

    function clearCache(Request $request)
    {
        Cache::delete(CacheKey::MainNavData->value);
        return new SuccessJsonResponse();
    }

    function delete(Request $request)
    {
        $category_hash = $request->get('category');
        if (empty($category_hash)) {
            return new ErrorJsonResponse('Invalid request');
        }
        $category_model = MainNavModel::byHashOrFail($category_hash);

        try {
            DB::beginTransaction();
            $category_model->delete();
            DB::commit();
            return new SuccessJsonResponse();
        } catch (\Exception|\Throwable $e) {
            DB::rollBack();
            return new ErrorJsonResponse($e->getMessage());
        }
    }
}
