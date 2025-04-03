<?php

namespace Brucelwayne\Admin\Controllers;

use Brucelwayne\AI\Jobs\CategorySEOJob;
use Brucelwayne\AI\Jobs\CategoryTranslateJob;
use Brucelwayne\AI\Traits\HasAIJobRequest;
use Brucelwayne\AI\Traits\HasAIJobStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Mallria\App\Models\AppModel;
use Mallria\Category\Enums\CategoryPackage;
use Mallria\Category\Enums\CategoryStatus;
use Mallria\Category\Models\CategoryTranslationModel;
use Mallria\Category\Models\TransCategoryModel;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\ErrorJsonResponse;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Core\Models\Team;
use Mallria\Core\Models\User;
use Mallria\Media\Models\MediableModel;
use Mallria\Media\Models\MediaModel;
use Mallria\Shop\Models\ShopModel;

class CategoryController extends BaseAdminController
{
    protected $modelHashNameInRequestParams = 'category';
    protected $jobModelClassName = TransCategoryModel::class;
    protected $translateJobClassName = CategoryTranslateJob::class;
    protected $seoJobClassname = CategorySEOJob::class;
    use HasAIJobRequest;
    use HasAIJobStatus;

    function sub(Request $request)
    {
        $category_hash = $request->get('category');
        $category_model = TransCategoryModel::byHashOrFail($category_hash);
        if (empty($category_model)) {
            return new ErrorJsonResponse('Nothing find!');
        }
        $sub = $category_model::where('parent_id', $category_model->getKey())
            ->defaultOrder()
            ->get()->toTree();

        $sub = $this->attachJobStatusToModels($sub, 'category_id');

        return new SuccessJsonResponse([
            'sub' => $sub,
        ]);
    }

    function updateFiles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => ['required', 'max:32'],
            'icon_type' => ['nullable'],
            'icon_svg' => ['nullable'],
            'icon_image' => ['nullable'],
            'image_id' => ['nullable'],
            'show_in_gallery' => ['nullable'],
            'video_id' => ['nullable'],
            'image_ids' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first());
        }

        $validated = $validator->validated();

        $category_hash = Arr::get($validated, 'category');
        $category_model = TransCategoryModel::byHashOrFail($category_hash);

        $category_model->update([
            'icon_type' => Arr::get($validated, 'icon_type'),
            'icon_svg' => Arr::get($validated, 'icon_svg'),
            'icon_image' => Arr::get($validated, 'icon_image'),
        ]);

        $image_id = Arr::get($validated, 'image_id');
        $image_id = empty($image_id) ? null : MediaModel::hashToId($image_id);

        $show_in_gallery = Arr::get($validated, 'show_in_gallery', false);

        $video_id = Arr::get($validated, 'video_id');
        $video_id = empty($video_id) ? null : MediaModel::hashToId($video_id);

        $image_ids = Arr::get($validated, 'image_ids');
        $image_ids = empty($image_ids) ? [] : collect($image_ids)->map(function (
            $image_id_hash
        ) {
            if (!empty($image_id_hash)) {
                return MediaModel::hashToId($image_id_hash);
            }
        })->toArray();

        if (MediableModel::saveMediable($category_model, [
            'image_id' => $image_id,
            'show_in_gallery' => $show_in_gallery,
            'video_id' => $video_id,
            'image_ids' => $image_ids,
        ])) {
            $category_model->load(['mediable']);
        }

        return new SuccessJsonResponse([
            'category' => $category_model
        ]);
    }

    function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => ['sometimes', 'max:32'],
            'package' => ['sometimes', 'max:32'],
            'translations' => ['sometimes'],
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first());
        }

        $validated = $validator->validated();

        $category_hash = Arr::get($validated, 'category');
        $category_model = TransCategoryModel::byHashOrFail($category_hash);

        $package = Arr::get($validated, 'package');
        if (!empty($package)) {
            $package = CategoryPackage::from($package);
        }
        $category_model->update([
            'package' => $package,
        ]);

        $translations = Arr::get($validated, 'translations');
        if (!empty($translations)) {
            foreach ($translations as $localeCode => $fields) {
                $name = Arr::get($fields, 'name');
                $description = Arr::get($fields, 'description');
                $size_guide = Arr::get($fields, 'size_guide');
                if (empty($name)) {
                    continue;
                }
                /**
                 * @var CategoryTranslationModel $category_translation
                 */
                $category_translation = $category_model->translateOrNew($localeCode);
                $category_translation->name = $name;
                $category_translation->description = $description;
                $category_translation->size_guide = $size_guide;
                $category_translation->save();
            }
            $category_model->save();
        }

        return new SuccessJsonResponse([
            'category' => $category_model,
        ]);

    }

    function up(Request $request)
    {
        $category_hash = $request->post('category');
        $category_model = TransCategoryModel::byHashOrFail($category_hash);
        $result = $category_model->up();
        return new SuccessJsonResponse([
            'result' => $result
        ]);
    }

    function down(Request $request)
    {
        $category_hash = $request->post('category');
        $category_model = TransCategoryModel::byHashOrFail($category_hash);
        $result = $category_model->down();
        return new SuccessJsonResponse([
            'result' => $result
        ]);
    }

    function status(Request $request)
    {
        $checked = $request->post('checked', false);
        $category_hash = $request->post('category');
        $category = TransCategoryModel::byHashOrFail($category_hash);
        if ($checked) {
            $category->update(['status' => CategoryStatus::Public->value]);
        } else {
            $category->update(['status' => CategoryStatus::Draft->value]);
        }

        return new SuccessJsonResponse([
            'category' => $category,
        ]);
    }

    function delete(Request $request)
    {
        $category_hash = $request->get('category');
        if (empty($category_hash)) {
            return new ErrorJsonResponse('Invalid request');
        }
        $category_model = TransCategoryModel::byHashOrFail($category_hash);

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

    function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package' => ['sometimes', 'max:32', new Enum(CategoryPackage::class)],
            'tenant' => ['sometimes', 'max:32'],
            'user' => ['sometimes', 'max:32'],
            'shop' => ['sometimes', 'max:32'],
            'app' => ['sometimes', 'max:32'],
        ]);
        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first());
        }
        $validated = $validator->validated();

        $package = Arr::get($validated, 'package');
        if (!empty($package)) {
            $package = CategoryPackage::from($package);
        }
        $team_hash = Arr::get($validated, 'team');
        $user_hash = Arr::get($validated, 'user');
        $shop_hash = Arr::get($validated, 'shop');
        $app_hash = Arr::get($validated, 'app');

        $team = null;
        if (!empty($team_hash)) {
            $team = Team::byHashOrFail($team_hash);
        }

        $user = null;
        if (!empty($user_hash)) {
            $user = User::byHashOrFail($user_hash);
        }

        $shop = null;
        if (!empty($shop_hash)) {
            $shop = ShopModel::byHashOrFail($shop_hash);
        }

        $app = null;
        if (!empty($app_hash)) {
            $app = AppModel::byHashOrFail($app_hash);
        }

        $categories = TransCategoryModel::when($package, function ($query) use ($package) {
            return $query->where('package', $package->value);
        })
            ->when($team, function ($query) use ($team) {
                return $query->where('team_id', $team->getKey());
            })->when($user, function ($query) use ($user) {
                return $query->where('user_id', $user->getKey());
            })->when($shop, function ($query) use ($shop) {
                return $query->where('shop_id', $shop->getKey());
            })->when($app, function ($query) use ($app) {
                return $query->where('app_id', $app->getKey());
            })
            ->whereNull('parent_id')
            ->defaultOrder()
            ->get()
            ->toTree();

        $categories = $this->attachJobStatusToModels($categories, 'category_id');

        return InertiaAdminFacade::render('Admin/Category/Index', [
            'categories' => $categories,
        ]);
    }

    function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => ['sometimes'],
            'package' => ['sometimes', 'max:32', new Enum(CategoryPackage::class)],
            'tenant' => ['sometimes', 'max:32'],
            'user' => ['sometimes', 'max:32'],
            'shop' => ['sometimes', 'max:32'],
            'app' => ['sometimes', 'max:32'],
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first());
        }

        $validated = $validator->validated();

        $package = Arr::get($validated, 'package');
        if (!empty($package)) {
            $package = CategoryPackage::from($package);
        }
        $team_hash = Arr::get($validated, 'team');
        $user_hash = Arr::get($validated, 'user');
        $shop_hash = Arr::get($validated, 'shop');
        $app_hash = Arr::get($validated, 'app');

        $team = null;
        if (!empty($team_hash)) {
            $team = Team::byHashOrFail($team_hash);
        }

        $user = null;
        if (!empty($user_hash)) {
            $user = User::byHashOrFail($user_hash);
        }

        $shop = null;
        if (!empty($shop_hash)) {
            $shop = ShopModel::byHashOrFail($shop_hash);
        }

        $app = null;
        if (!empty($app_hash)) {
            $app = AppModel::byHashOrFail($app_hash);
        }

        $categories = TransCategoryModel::search(urldecode($validated['q']))
            ->when($package, function ($query) use ($package) {
                return $query->where('package', $package->value);
            })
            ->when($team, function ($query) use ($team) {
                return $query->where('team_id', $team->getKey());
            })->when($user, function ($query) use ($user) {
                return $query->where('user_id', $user->getKey());
            })->when($shop, function ($query) use ($shop) {
                return $query->where('shop_id', $shop->getKey());
            })->when($app, function ($query) use ($app) {
                return $query->where('app_id', $app->getKey());
            })
            ->paginate(10);

        if (!is_empty($categories)) {
            foreach ($categories as $category) {
                $ancestors = $category->getAncestors();
                $category->setAttribute('ancestors', $ancestors);
                $path = collect($ancestors)->pluck('name');
                $category->setAttribute('path', $path);
            }
        }

        return new SuccessJsonResponse([
            'categories' => $categories,
        ]);
    }

    function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package' => ['sometimes', 'max:32', new Enum(CategoryPackage::class)],
            'tenant' => ['sometimes', 'max:32'],
            'user' => ['sometimes', 'max:32'],
            'shop' => ['sometimes', 'max:32'],
            'app' => ['sometimes', 'max:32'],
            'name' => ['required', 'max:32'],
            'description' => ['nullable', 'max:1000'],
            'locale' => ['required', 'max:32'],
            'parent_category' => ['sometimes', 'max:32'],

            'image_id' => ['nullable'],
            'show_in_gallery' => ['nullable'],
            'video_id' => ['nullable'],
            'image_ids' => ['nullable'],

            'size_guide' => ['nullable'],
        ], [
            'regex' => 'The :attribute must contain only letters and spaces, without punctuation.',
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first());
        }

        $validated = $validator->validated();

        $package = Arr::get($validated, 'package');
        if (!empty($package)) {
            $package = CategoryPackage::from($package);
        }
        $team_hash = Arr::get($validated, 'team');
        $user_hash = Arr::get($validated, 'user');
        $shop_hash = Arr::get($validated, 'shop');
        $app_hash = Arr::get($validated, 'app');

        $data = [
            'package' => $package,
        ];

        $team = null;
        if (!empty($team_hash)) {
            $team = Team::byHashOrFail($team_hash);
            $data['team_id'] = $team->getKey();
        }

        $user = null;
        if (!empty($user_hash)) {
            $user = User::byHashOrFail($user_hash);
            $data['user_id'] = $user->getKey();
        }

        $shop = null;
        if (!empty($shop_hash)) {
            $shop = ShopModel::byHashOrFail($shop_hash);
            $data['shop_id'] = $shop->getKey();
        }

        $app = null;
        if (!empty($app_hash)) {
            $app = AppModel::byHashOrFail($app_hash);
            $data['app_id'] = $app->getKey();
        }


        $locale = Arr::get($validated, 'locale');
        $name = Arr::get($validated, 'name');
        $description = Arr::get($validated, 'description');
        $size_guide = Arr::get($validated, 'size_guide');

        $data['name'] = $name;
        $data['description'] = $description;
        $data['size_guide'] = $size_guide;

        $parent_hash = Arr::get($validated, 'parent_category');
        if (!empty($parent_hash)) {
            $parent_model = TransCategoryModel::byHashOrFail($parent_hash);
            $data['parent_id'] = $parent_model->getKey();
        }

        $data['status'] = CategoryStatus::Public->value;

        App::setLocale($locale);
        $category_model = TransCategoryModel::create($data);

        $image_id = Arr::get($validated, 'image_id');
        $image_id = empty($image_id) ? null : MediaModel::hashToId($image_id);

        $show_in_gallery = Arr::get($validated, 'show_in_gallery', false);

        $video_id = Arr::get($validated, 'video_id');
        $video_id = empty($video_id) ? null : MediaModel::hashToId($video_id);

        $image_ids = Arr::get($validated, 'image_ids');
        $image_ids = empty($image_ids) ? [] : collect($image_ids)->map(function (
            $image_id_hash
        ) {
            if (!empty($image_id_hash)) {
                return MediaModel::hashToId($image_id_hash);
            }
        })->toArray();

        if (MediableModel::saveMediable($category_model, [
            'image_id' => $image_id,
            'show_in_gallery' => $show_in_gallery,
            'video_id' => $video_id,
            'image_ids' => $image_ids,
        ])) {
            $category_model->load(['mediable']);
        }

        return new SuccessJsonResponse([
            'category' => $category_model,
        ]);

    }

    function create(Request $request)
    {
        return InertiaAdminFacade::render('Admin/Category/Create');
    }

    function edit(Request $request)
    {
        $category_hash = $request->get('category');
        $category = TransCategoryModel::byHashOrFail($category_hash);

        $locale = $request->get('locale');


        return InertiaAdminFacade::render('Admin/Category/Edit', [
            'category' => $category,
            'locale' => $locale,
        ]);
    }
}