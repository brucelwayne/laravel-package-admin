<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mallria\Business\Models\BusinessModel;
use Mallria\Core\Enums\PostStatus;
use Mallria\Core\Enums\PostType;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Shop\Models\TransProductModel;

class SellerController extends BaseAdminController
{
    function index(Request $request)
    {
//        $tenants = BusinessModel::where('verified', false)->cursorPaginate(20);
        $tenants = BusinessModel::query()->cursorPaginate(20);
        return InertiaAdminFacade::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
        ]);
    }

    function updateStatus(Request $request)
    {
        $validated = $request->validate([
            '*.tenant' => 'required|string',
            '*.verified' => 'required|integer|in:0,1'
        ]);

        $tenant_ids = [];
        $product_ids = [];
        DB::transaction(function () use ($validated, &$tenant_ids, &$product_ids) {
            // 获取+更新店铺状态
            $tenants = collect($validated)->map(function ($it) {
                $tenant = BusinessModel::byHashOrFail($it['tenant']);
                $tenant->verified = $it['verified'];
                return $tenant;
            });
            $tenants->each->save();
            $tenant_ids = $tenants->pluck('id')->all();

            // 按verified分组（状态只有0和1）
            $grouped_tenants = $tenants->groupBy('verified');
            // 批量处理关联产品状态
            foreach ($grouped_tenants as $verified => $tenants_group) {
                // 确定状态转换
                $new_status = $verified ? PostStatus::Published : PostStatus::Unverified;
                $current_status = $verified ? PostStatus::Unverified : PostStatus::Published;

                $ids = TransProductModel::query()
                    ->where('type', PostType::Product)
                    ->whereIn('team_id', $tenants_group->pluck('id'))
                    ->where('status', $current_status)
                    ->pluck('id')
                    ->toArray();

                if (!empty($ids)) {
                    TransProductModel::whereIn('id', $ids)
                        ->update(['status' => $new_status]);
                    $product_ids = array_merge($product_ids, $ids);
                }
            }

        });

        // 事务提交后再同步搜索引擎
        if (!empty($tenant_ids)) {
            BusinessModel::whereIn('id', $tenant_ids)->searchable();
        }
        if (!empty($product_ids)) {
            TransProductModel::whereIn('id', $product_ids)->searchable();
        }

        return new SuccessJsonResponse();
    }
}