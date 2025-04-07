<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
use Mallria\Core\Enums\PostType;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Main\Models\FeatureTagModel;
use Mallria\Shop\Models\TransProductModel;

class FeatureTagController extends BaseAdminController
{
    function index(Request $request)
    {
        $currentTag = $request->get('tag', 'all');
        $tags = config('mallria-main.feature-tags');

        if (empty($currentTag) || $currentTag === 'all') {
            $product_models = TransProductModel::with([
                'translations',
                'metarelation',
                'featureTags',
                'featureTags.translations',
                'mediable'
            ])
                ->where('type', PostType::Product)
                ->cursorPaginate(20);
        } else {
            $currentTagModel = FeatureTagModel::byHashOrFail($currentTag);
            $currentTagId = $currentTagModel->getKey();
            $product_models = TransProductModel::with([
                'translations',
                'metarelation',
                'featureTags',
                'featureTags.translations',
                'mediable'
            ])
                ->whereHas('featureTags', function ($query) use ($currentTagId) {
                    $query->where('tag_id', $currentTagId);
                })
                ->where('type', PostType::Product)
                ->cursorPaginate(20);
        }

        return InertiaAdminFacade::render('Admin/FeatureTag/Index', [
            'products' => $product_models,
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