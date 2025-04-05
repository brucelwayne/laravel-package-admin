<?php


use Brucelwayne\Admin\Controllers\ExternalPostController;
use Brucelwayne\Admin\Controllers\ExternalSellerController;
use Brucelwayne\Admin\Controllers\SEONewPostController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.url-prefix'))
    ->name('admin.')
    ->middleware(['web', 'auth.admin'])
    ->group(function () {

        Route::get('external/posts', [ExternalPostController::class, 'posts'])
            ->name('external.posts');
        Route::get('external/sellers', [ExternalSellerController::class, 'sellers'])
            ->name('external.sellers');
        Route::get('external/new-posts', [ExternalSellerController::class, 'newPosts'])
            ->name('external.new-posts');
        Route::get('external/new-posts-by-seller', [ExternalSellerController::class, 'newPostsBySeller'])
            ->name('external.new-posts-by-seller');
        Route::post('external/favorite-new-post', [ExternalSellerController::class, 'favoriteNewPost'])
            ->name('external.favorite-new-post');
        Route::get('external/favorited-posts', [ExternalSellerController::class, 'favoritedPosts'])
            ->name('external.favorited-posts');
        Route::match(['get', 'post', 'delete'], 'external/favorited-posts-category', [ExternalSellerController::class, 'favoritedPostCategory'])
            ->name('external.favorited-posts.category');
        Route::get('external/post', [ExternalSellerController::class, 'single'])
            ->name('external.post.single');

        Route::post('external/sync-categories', [ExternalSellerController::class, 'syncCategory'])
            ->name('external.sync-categories');

        Route::get('external/download-favorite', [ExternalSellerController::class, 'exportFavoriteToExcel'])
            ->name('external.download-favorite');

        Route::get('external/sellers/create', [ExternalSellerController::class, 'create'])
            ->name('external.sellers.create');

        Route::get('seo/new-post/get-job', [SEONewPostController::class, 'getJob'])
            ->name('seo.new-post.get-job');
        Route::post('seo/new-post/forward', [SEONewPostController::class, 'newPost'])
            ->name('seo.new-post.forward');
        Route::post('seo/post/save-downloaded-medias', [SEONewPostController::class, 'saveDownloadedMedias'])
            ->name('seo.post.save-downloaded-medias');


        Route::post('shop/forward-post', [ExternalPostController::class, 'forward'])
            ->name('shop.forward-post');

    });
