<?php

namespace Brucelwayne\Admin\Controllers;

use Brucelwayne\AI\Jobs\PageSEOAJob;
use Brucelwayne\AI\Jobs\PageTranslateJob;
use Brucelwayne\AI\Traits\HasAIJobRequest;
use Brucelwayne\AI\Traits\HasAIJobStatus;
use Brucelwayne\SEO\Traits\HasSEOIndexRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Mallria\Core\Enums\SEOFrequency;
use Mallria\Core\Facades\InertiaAdminFacade;
use Mallria\Core\Http\Responses\ErrorJsonResponse;
use Mallria\Core\Http\Responses\SuccessJsonResponse;
use Mallria\Core\Models\PageModel;
use Mallria\Main\Enums\MallriaPage;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class PagesController extends BaseAdminController
{
    protected $modelHashNameInRequestParams = 'page';
    protected $jobModelClassName = PageModel::class;
    protected $translateJobClassName = PageTranslateJob::class;
    protected $seoJobClassname = PageSEOAJob::class;
    use HasAIJobRequest;
    use HasAIJobStatus;
    use HasSEOIndexRequest;

    function search(Request $request)
    {
        $keywords = $request->get('q');
        if (empty($keywords)) {
            return new SuccessJsonResponse();
        } else {
            $page_models = PageModel::search($keywords)->paginate(20);
        }

        return new SuccessJsonResponse([
            'pages' => $page_models,
        ]);
    }

    function create(Request $request)
    {

        $validator = Validator::make($request->post(), [
            'domain' => ['required', 'string', 'max:255'],
            'route' => ['required', 'string', 'max:255'],
            'frequency' => ['required', 'string', new Enum(SEOFrequency::class)], // 验证是否为枚举值
            'priority' => ['required', 'numeric', 'between:0.1,1.0'], // 验证在0.1到1.0之间
        ], [
            'domain.required' => '域名不能为空。',
            'domain.string' => '域名必须是字符串。',
            'route.required' => '路由不能为空。',
            'route.string' => '路由必须是字符串。',
            'frequency.required' => '更新频率不能为空。',
            'frequency.in' => '更新频率值不合法。',
            'priority.required' => '权重不能为空。',
            'priority.numeric' => '权重必须是数字。',
            'priority.between' => '权重必须在 0.1 和 1.0 之间。',
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first(), [
                'errors' => $validator->errors(),
            ], 422);
        }

        $domain = $request->post('domain');
        $route = $request->post('route');
        $frequency = $request->post('frequency');
        $priority = $request->post('priority');

        if (empty($domain) || empty($route)) {
            return new ErrorJsonResponse('无效的请求');
        }

        $page_model = PageModel::byDomainRoute($domain, $route);
        if (!empty($page_model)) {
            return new ErrorJsonResponse('该页面已经存在');
        }

        $page_model = PageModel::create([
            'domain' => $domain,
            'route' => $route,
            'frequency' => $frequency,
            'priority' => $priority,
        ]);

        if (empty($page_model)) {
            return new ErrorJsonResponse('无法创建页面');
        }

        return new SuccessJsonResponse([
            'page' => $page_model,
        ]);
    }

    function edit(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'page' => ['required', 'string', 'max:36'],
            'domain' => ['required', 'string', 'max:255'],
            'route' => ['required', 'string', 'max:255'],
            'frequency' => ['required', 'string', new Enum(SEOFrequency::class)], // 验证是否为枚举值
            'priority' => ['required', 'numeric', 'between:0.1,1.0'], // 验证在0.1到1.0之间
        ], [
            'domain.required' => '域名不能为空。',
            'domain.string' => '域名必须是字符串。',
            'route.required' => '路由不能为空。',
            'route.string' => '路由必须是字符串。',
            'frequency.required' => '更新频率不能为空。',
            'frequency.in' => '更新频率值不合法。',
            'priority.required' => '权重不能为空。',
            'priority.numeric' => '权重必须是数字。',
            'priority.between' => '权重必须在 0.1 和 1.0 之间。',
        ]);

        if ($validator->fails()) {
            return new ErrorJsonResponse($validator->errors()->first(), [
                'errors' => $validator->errors(),
            ], 422);
        }

        $domain = $request->post('domain');
        $route = $request->post('route');
        $frequency = $request->post('frequency');
        $priority = $request->post('priority');

        if (empty($domain) || empty($route)) {
            return new ErrorJsonResponse('无效的请求');
        }

        $page_model = PageModel::byHashOrFail($request->post('page'));

        $exist_page_model = PageModel::byDomainRoute($domain, $route);

        if (!empty($exist_page_model)) {
            if ($page_model->getKey() !== $exist_page_model->getKey()) {
                return new ErrorJsonResponse(__('该域名下已经有重复的route的页面！'));
            }
        }

        $result = $page_model->update([
            'domain' => $domain,
            'route' => $route,
            'frequency' => $frequency,
            'priority' => $priority,
        ]);

        if ($result) {
            return new SuccessJsonResponse([
                'page' => $page_model,
            ]);
        }
        return new ErrorJsonResponse('无法更新页面');
    }

    function index(Request $request)
    {
        $supported_locales = LaravelLocalization::getSupportedLocales();

        // Retrieve the page models with pagination
        $page_models = PageModel::orderBy('id', 'desc')->paginate(10);

        // Eager load translations for each PageModel
        $page_models->load(['translations']);

//        // Flatten all translations into a single collection
//        $translations = $page_models->getCollection()->pluck('translations')->flatten();
//
//        // Attach job status to all translations
//        $attached_jobs_translations = AIJobFacade::attachJobStatus($translations);
//
//        $attached_jobs_page_models = AIJobFacade::attachJobStatus($page_models->getCollection());
//
//        // Reattach the translations to the page models
//        $page_models->getCollection()->each(function ($page_model) use ($attached_jobs_page_models, $attached_jobs_translations) {
//
//            $page_model = $attached_jobs_page_models->where('id', $page_model->getKey())->first();
//
//            // Find the corresponding translations for each page_model
//            $page_model_translations = $attached_jobs_translations->where('page_id', $page_model->getKey());
//
//            // Set the translations attribute for the page model
//            $page_model->setRelation('translations', $page_model_translations);
//            return $page_model;
//        });

        $page_models = $this->attachJobStatusToModels($page_models, 'page_id');

        $routes = MallriaPage::getSelectOptions();


        return InertiaAdminFacade::render('Admin/Pages/Index', [
            'pages' => $page_models,
            'routes' => $routes
        ]);
    }

    function translate(Request $request)
    {
        $validatedData = $request->validate([
            'page' => 'required|max:32',
            'domain' => 'required|string|max:199',
            'route' => 'required|string|max:199',
            'locale' => 'required|string',
            'title' => 'required|string|max:500',
            'excerpt' => 'required|string|max:1000',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:255',
            'content_type' => 'required|string|in:html,markdown',
            'content' => 'nullable',
        ], [
            'domain.required' => '请选择域名!',
            'domain.in' => '域名无效!',
            'route.required' => '请输入Route!',
            'route.in' => '无效的Route!',
            'locale.required' => '请选择语言!',
            'locale.in' => '语言无效!',
            'title.required' => '请输入标题!',
            'excerpt.required' => '请输入内容!',
            'keywords.*.string' => '关键字必须是字符串!',
            'keywords.*.max' => '每个关键字不得超过255个字符!',
        ]);

        $page_hash = $validatedData['page'];
        $page_model = PageModel::byHashOrFail($page_hash);

        App::setLocale($validatedData['locale']);

        $page_model->domain = $validatedData['domain'];
        $page_model->route = $validatedData['route'];
        $page_model->title = $validatedData['title'];
        $page_model->excerpt = $validatedData['excerpt'];
        $page_model->keywords = $validatedData['keywords'];
        $page_model->content_type = $validatedData['content_type'];
        $page_model->content = $validatedData['content'];
        $page_model->save();

//        $page_model_translate = $page_model->translateOrNew($validatedData['locale']);
//        /**
//         * @var PageTranslationModel $page_model_translate
//         */
//        $page_model_translate->title = $validatedData['title'];
//        $page_model_translate->description = $validatedData['description'];
//        $page_model_translate->keywords = $validatedData['keywords'];
//        $page_model_translate->content = $validatedData['content'];
//        $page_model_translate->save();
//        $page_model->refresh();

        return new SuccessJsonResponse([
            'page' => $page_model,
        ]);
    }
}