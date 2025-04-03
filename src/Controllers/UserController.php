<?php

namespace Brucelwayne\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Mallria\App\Facades\AppFacade;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Facades\IpAddressFacade;
use Mallria\Core\Http\Responses\ErrorJsonResponse;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Core\Models\HandleModel;
use Mallria\Core\Models\User;
use Mallria\Media\Models\MediaModel;
use Mallria\Passport\Models\UserStateModel;

class UserController extends BaseAdminController
{

    function updateProfile(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'user_id' => 'required|string|max:32',
            'avatar_id' => 'nullable|string|max:32', // 验证 avatar_id 必须是字符串且最大长度为 255
            'email' => 'required|email|max:32',      // 验证 email 必须是有效的电子邮件格式且最大长度为 255
            'handle' => 'required|string|min:3|max:32',     // 验证 handle 必须是字符串且最大长度为 50
            'name' => 'required|string|max:100',       // 验证 name 必须是字符串且最大长度为 100
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first(), [
                'errors' => $validator->errors(),
            ]);
        }

        try {
            DB::beginTransaction();
            $validated = $validator->validated();

            $user_hash = Arr::get($validated, 'user_id');
            $avatar_hash = Arr::get($validated, 'avatar_id');
            $email = trim(Arr::get($validated, 'email'));

            $name = trim(Arr::get($validated, 'name'));

            $dataToUpdate = [
                'email' => $email,
                'name' => $name,
            ];
            $user = User::byHashOrFail($user_hash);

            //检查头像
            if (!empty($avatar_hash)) {
                $avatar = MediaModel::byHashOrFail($avatar_hash);
                $dataToUpdate['avatar_id'] = $avatar->getKey();
            }

            $user->update($dataToUpdate);

            $handle_name = Arr::get($validated, 'handle');
            if (!empty($handle_name)) {
                $handle_name = generate_handle_name_from_string(trim($handle_name));
                $handle_model = HandleModel::where('name', $handle_name)->first();
                if (!empty($handle_model)) {
                    if ($handle_model->handleable_type === User::TABLE) {
                        if ($handle_model->handleable_id !== $user->getKey()) {
                            throw new \Exception('@账户已被其他用户占用！');
                        }
                        //如果是自己的，也无需更新
                    }
                } else {
                    // 如果没有找到 handle，检查用户是否已经有 handle
                    $user->handle()->updateOrCreate(
                        ['handleable_id' => $user->getKey(), 'handleable_type' => $user->getMorphClass()], // 查找条件
                        ['name' => $handle_name] // 要更新或插入的数据
                    );
                }
            }
            DB::commit();
            return new SuccessJsonResponse();
        } catch (\Exception|\Throwable $e) {
            DB::rollBack();
            return new ErrorJsonResponse($e->getMessage());
        }
    }

    function registered(Request $request)
    {
        $users = User::orderBy('id', 'desc')->cursorPaginate(10);
        return InertiaAdminFacade::render('Admin/User/Registered', [
            'users' => $users,
        ]);
    }

    function active(Request $request)
    {
        // 获取 start 和 end 参数，如果不存在则使用今天的开始和结束时间
        $start = $request->input('start', Carbon::today()->startOfDay()->format('Y-m-d'));
        $end = $request->input('end', Carbon::today()->endOfDay()->format('Y-m-d'));

        // 转换成 Carbon 对象
        $startOfDay = Carbon::parse($start)->startOfDay();
        $endOfDay = Carbon::parse($end)->endOfDay();

        // 查询活跃的 UserStateModel 并加载相关用户数据
        $user_state_models = UserStateModel::with(['user'])
            ->whereBetween('updated_at', [$startOfDay, $endOfDay])
            ->orderBy('updated_at', 'desc')
            ->cursorPaginate(10);

        // 查询每个 UserStateModel 的 IP 地址数据
        foreach ($user_state_models as $user_state) {
            $user_state->ip_address_data = IpAddressFacade::search($user_state->ip_address);
        }

        // 查询指定时间段内唯一设备的数量
        $uniqueDeviceCount = UserStateModel::whereBetween('updated_at', [$startOfDay, $endOfDay])
            ->distinct('device_id')
            ->count('device_id');

        // 将查询结果传递给 Inertia 组件
        return InertiaAdminFacade::render('Admin/User/Active', [
            'users_states' => $user_state_models,
            'unique_device_count' => $uniqueDeviceCount,
            'query' => [
                'start' => $startOfDay->toDateString(),
                'end' => $endOfDay->toDateString(),
            ],
        ]);
    }

    function search(Request $request)
    {
        $app_model = AppFacade::getOrFail();

        $keywords = urldecode($request->get('q'));
        if (empty($keywords)) {
            return new SuccessJsonResponse();
        }

        $user_models = User::search($keywords)
            ->orderBy('created_at', 'desc')
            ->cursorPaginate(10);

        return new SuccessJsonResponse([
            'users' => $user_models,
        ]);
    }
}