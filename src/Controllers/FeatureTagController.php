<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
use Mallria\Core\Enums\PostStatus;
use Mallria\Core\Enums\PostType;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Main\Models\FeatureTagModel;
use Mallria\Shop\Models\PublishedTransProductModel;
use Mallria\Shop\Models\TransProductModel;

class FeatureTagController extends BaseAdminController
{

    const Relations = ['translations',
        'metarelation',
        'featureTags',
        'featureTags.translations',
        'mediable'];

    function index(Request $request)
    {
        $keywords = $request->get('q');
        $currentTag = $request->get('tag', 'all');
        $tags = config('mallria-main.feature-tags');

        $productQuery = PublishedTransProductModel::with(self::Relations);

        // 如果是搜索模式
        if (!empty($keywords)) {
            $productQuery = PublishedTransProductModel::search($keywords)
                ->where('type', PostType::Product->value)
                ->where('status', PostStatus::Published->value);

            // 若指定了某个 tag，则过滤
            if ($currentTag !== 'all') {
                $tagModel = FeatureTagModel::byHashOrFail($currentTag);
                $productQuery->whereIn('feature_tag_ids', [$tagModel->getKey()]);
            }

            $productModels = $productQuery->paginate(20);

            $productModels->load(self::Relations);

        } else {
            // 非搜索模式，根据 tag 过滤
            if ($currentTag !== 'all') {
                $tagModel = FeatureTagModel::byHashOrFail($currentTag);
                $productQuery->whereHas('featureTags', function ($query) use ($tagModel) {
                    $query->where('tag_id', $tagModel->getKey());
                });
            }

            $productModels = $productQuery->cursorPaginate(20);
        }

        return InertiaAdminFacade::render('Admin/FeatureTag/Index', [
            'products' => $productModels,
            'tags' => $tags,
            'tag' => $currentTag,
        ]);
    }


    function toggle(Request $request)
    {
        // 验证输入
        $validated = $request->validate([
            'product' => 'required|string|max:32',
            'tag' => 'required|string|max:32',
        ]);

        $productHash = $validated['product'];
        $tagHash = $validated['tag'];

        // 查找产品和标签
        $product = TransProductModel::byHashOrFail($productHash);
        $tag = FeatureTagModel::byHashOrFail($tagHash);

        // 检查产品是否支持特性标签 (featureTaggable)
        $existingRelation = $product->featureTags()->where('tag_id', $tag->id)->exists();

        if ($existingRelation) {
            // 删除关联
            $product->featureTags()->detach($tag->id);
            $status = 'detached';
        } else {
            // 创建关联
            $product->featureTags()->attach($tag->id);
            $status = 'attached';
        }

        return new SuccessJsonResponse([
            'action' => $status,
            'product' => $product->hash,
            'tag' => $tag->hash,
        ]);
    }
}