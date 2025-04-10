<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mallria\Category\Models\TransCategoryModel;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Main\Enums\CacheKey;
use Mallria\Media\Models\MediaModel;
use Overtrue\LaravelOptions\Facade as OptionFacade;

class HomePageController extends BaseAdminController
{

    function index()
    {
        return InertiaAdminFacade::render('Admin/HomePage/Index');
    }

    function getOption(Request $request)
    {
        $key = $request->get('key');
        $data = null;
        if (!empty($key)) {
            $data = OptionFacade::get($key);
            if (!empty($data)) {
                // 提取所有 category id
                $category_ids = [];
                foreach ($data as $item) {
                    if (!empty($item['category']) && !empty($item['category']['id'])) {
                        $category_ids[] = $item['category']['id']; // 追加 ID 到数组
                    }
                }

                // 查询分类记录并按 ID 索引
                $categories = !empty($category_ids)
                    ? TransCategoryModel::whereIn('id', $category_ids)->get()->keyBy('id')
                    : collect();

                // 替换 category 字段
                foreach ($data as &$item) { // 使用引用以修改原数组
                    $category = $categories->get($item['category']['id'], null);
                    if (!empty($category)) {
                        $ancestors = $category->getAncestors();
                        $category->setAttribute('ancestors', $ancestors);
                        $path = collect($ancestors)->pluck('name');
                        $category->setAttribute('path', $path);
                    }
                    if (!empty($item['category']) && !empty($item['category']['id'])) {
                        $item['category'] = $category; // 替换为完整分类对象或 null
                    } else {
                        $item['category'] = null; // 没有分类时设为 null
                    }
                }
                unset($item); // 解除引用
            }
        }

        return new SuccessJsonResponse([
            'data' => $data,
        ]);
    }

    function store(Request $request)
    {
        // 验证请求数据
        $validated = $request->validate([
            'name' => 'required|array|size:5', // 数组，5个元素，可为空
            'name.*' => 'required|string|max:32', // 每个名称可为空，最大32字符
            'category' => 'required|array|size:5',
            'category.*' => 'required|string|max:32', // 每个分类hash可为空，最大32字符
            'image' => 'required|array|size:5',
            'image.*' => 'required|string|max:32', // 每个图片hash可为空，最大32字符
        ]);

        // 获取输入数据
        $names = $request->input('name', array_fill(0, 5, null)); // 默认填充null
        $categoryHashes = $request->input('category', array_fill(0, 5, null));
        $imageHashes = $request->input('image', array_fill(0, 5, null));

        // 处理分类hash转换为ID并查询
        $categoryIds = collect($categoryHashes)
            ->filter() // 移除null值
            ->map(function ($hash) {
                return TransCategoryModel::hashToId($hash); // 假设hashToId返回ID或null
            })
            ->filter(); // 移除无效的ID（null）

        $categories = $categoryIds->isNotEmpty()
            ? TransCategoryModel::whereIn('id', $categoryIds)->get()->keyBy('id')
            : collect();

        // 处理图片hash转换为ID并查询
        $imageIds = collect($imageHashes)
            ->filter() // 移除null值
            ->map(function ($hash) {
                return MediaModel::hashToId($hash); // 假设hashToId返回ID或null
            })
            ->filter(); // 移除无效的ID（null）

        $images = $imageIds->isNotEmpty()
            ? MediaModel::whereIn('id', $imageIds)->get()->keyBy('id')
            : collect();

        // 构建数据
        $data = [];
        for ($i = 0; $i < 5; $i++) {
            $categoryId = TransCategoryModel::hashToId($categoryHashes[$i]);
            $imageId = MediaModel::hashToId($imageHashes[$i]);

            $item = [
                'name' => $names[$i],
                'category' => $categoryId ? ($categories[$categoryId] ?? null) : null,
                'image' => $imageId ? ($images[$imageId] ?? null) : null,
            ];

            $data[] = $item;
        }

        // 调试输出
        OptionFacade::set(CacheKey::PromoteCategory->value, $data);

        // 清除缓存
        Cache::forget(CacheKey::PromoteCategory->value);

        // 返回成功响应
        return new SuccessJsonResponse([
            'data' => $data,
        ]);
    }

}