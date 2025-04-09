<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Mallria\App\Facades\AppFacade;
use Mallria\Core\Enums\PostStatus;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\ErrorJsonResponse;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Shop\Models\TransProductModel;

class ProductController extends BaseAdminController
{
    function index(Request $request)
    {
        $products = TransProductModel::with(["mediable", "purchaseInfo"])
//            ->where('status', PostStatus::ReadyForApproval)
            ->cursorPaginate(20);

        return InertiaAdminFacade::render('Admin/Products/Index', [
            'products' => $products,
        ]);
    }

    function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'product' => 'required|string',
            'status' => 'required|integer',
        ]);

        $product = TransProductModel::byHashOrFail($validated['product']);

        $product->status = $validated['status'];
        $product->save();

        return new SuccessJsonResponse();
    }

    /**
     * 搜索产品
     *
     * 根据请求中的搜索条件查询产品并返回匹配的结果。
     *
     * @param Request $request
     */
    public function search(Request $request)
    {
        $app_model = AppFacade::getOrFail();

        $validator = Validator::make($request->input(), [
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first(), [
                'errors' => $validator->errors(),
            ]);
        }

        $searchTerm = urldecode(Arr::get($request->input(), 'q'));

        // 使用 Laravel Scout 进行搜索
        if ($searchTerm) {
            $products = TransProductModel::search($searchTerm)
                ->where('type', 'product')
                ->paginate(10);
        } else {
            $products = TransProductModel::where('app_id', $app_model->getKey())
                ->orderBy('updated_at', 'desc')
                ->paginate(10);
        }


        return new SuccessJsonResponse([
            'products' => $products,
        ]);
    }
}