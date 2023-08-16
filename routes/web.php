<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * Home and search pages
 */
Route::get('/home', function () {
    return redirect('');
})->name('home');
Route::get('', 'HomeController@index')->name('index');
Route::get('about', 'HomeController@aboutPage')->name('about');
Route::post('kmlpolygonsearch', 'GazetteerController@searchFromKmlPolygon')->name('searchFromKmlPolygon'); //search from file
Route::get('places/{id?}', 'GazetteerController@search')->name('places'); //shows places with optional id, if no id is given it uses all results before applying filters
Route::get('search', 'GazetteerController@search')->name('search')->middleware('checkmaxpaging', 'cors');
Route::get('maxpaging', 'GazetteerController@maxPagingMessage')->name('maxPagingMessage');
Route::get('maxpagingredirect', 'GazetteerController@maxPagingRedirect')->name('maxPagingRedirect');
Route::post('bulkfileparser', 'GazetteerController@bulkFileParser');

/**
 * Public dataset pages
 */
Route::get('publicdatasets', 'DatasetController@viewPublicDatasets')->name('publicdatasets');
Route::get('publicdatasets/{id}', 'DatasetController@viewPublicDataset')->name('publicdataset');
Route::get('publicdatasets/{id}/kml', 'DatasetController@viewPublicKML')->name('viewpublicdatasetkml')->middleware('cors');
Route::get('publicdatasets/{id}/kml/download', 'DatasetController@downloadPublicKML')->name('downloadpublicdatasetkml');
Route::get('publicdatasets/{id}/json', 'DatasetController@viewPublicJSON')->name('viewpublicdatasetjson')->middleware('cors');
Route::get('publicdatasets/{id}/json/download', 'DatasetController@downloadPublicJSON')->name('downloadpublicdatasetjson');
Route::get('publicdatasets/{id}/csv', 'DatasetController@viewPublicCSV')->name('viewpublicdatasetcsv')->middleware('cors');
Route::get('publicdatasets/{id}/csv/download', 'DatasetController@downloadPublicCSV')->name('downloadpublicdatasetcsv');
Route::get('publicdatasets/{id}/ro-crate', 'DatasetController@downloadPublicROCrate');

/**
 * Public collection pages.
 */
Route::get('publiccollections', 'CollectionController@viewPublicCollections');
Route::get('publiccollections/{id}', 'CollectionController@viewPublicCollection');
Route::get('publiccollections/{id}/json', 'CollectionController@viewPublicJson')->middleware('cors');
Route::get('publiccollections/{id}/ro-crate', 'CollectionController@downloadPublicROCrate');

/**
 * User Pages.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('myprofile', 'User\UserController@userProfile')->name('myProfile');
    Route::get('myprofile/mydatasets', 'User\UserController@userDatasets')->name('myDatasets'); //Only let users view own dataset
    Route::get('myprofile/mysearches', 'User\UserController@userSavedSearches')->name('mySearches');
    Route::post('myprofile/mysearches/delete', 'User\UserController@deleteUserSavedSearches');
    Route::get('myprofile/mydatasets/newdataset', 'User\UserController@newDatasetPage');
    Route::post('myprofile/mydatasets/newdataset/create', 'User\UserController@createNewDataset');
    Route::get('myprofile/mydatasets/{id}', 'User\UserController@userViewDataset'); //Only let users view own dataset
    Route::get('myprofile/mydatasets/{id}/collaborators', 'User\UserController@userEditCollaborators');
    Route::post('bulkadddataitem', 'User\UserController@bulkAddDataItem'); //not ajax as it is too much data
    Route::post('myprofile/mydatasets/{id}/edit', 'User\UserController@userEditDataset');
    Route::get('myprofile/edit', 'User\UserController@editUserPage')->name('editUserPage');
    Route::post('myprofile/edit/info', 'User\UserController@editUserInfo')->name('editUserInfo');
    Route::post('myprofile/edit/password', 'User\UserController@editUserPassword')->name('editUserPassword');
    Route::post('myprofile/edit/email', 'User\UserController@editUserEmail')->name('editUserEmail');
    Route::post('myprofile/mydatasets/join/{link?}', 'AjaxController@ajaxjoindataset'); //Join a dataset by link

    Route::get('myprofile/mydatasets/{id}/kml', 'DatasetController@viewPrivateKML')->name('viewdatasetkml');
    Route::get('myprofile/mydatasets/{id}/kml/download', 'DatasetController@downloadPrivateKML')->name('downloaddatasetkml');
    Route::get('myprofile/mydatasets/{id}/json', 'DatasetController@viewPrivateJSON')->name('viewdatasetjson');
    Route::get('myprofile/mydatasets/{id}/json/download', 'DatasetController@downloadPrivateJSON')->name('downloaddatasetjson');
    Route::get('myprofile/mydatasets/{id}/csv', 'DatasetController@viewPrivateCSV')->name('viewdatasetcsv');
    Route::get('myprofile/mydatasets/{id}/csv/download', 'DatasetController@downloadPrivateCSV')->name('downloaddatasetcsv');
    Route::get('myprofile/mydatasets/{id}/ro-crate', 'DatasetController@downloadPrivateROCrate');
});

/**
 * User collection CRUD pages
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('myprofile/mycollections', 'CollectionController@viewMyCollections');
    Route::get('myprofile/mycollections/newcollection', 'CollectionController@newCollection');
    Route::post('myprofile/mycollections/newcollection/create', 'CollectionController@createNewCollection');
    Route::get('myprofile/mycollections/{id}', 'CollectionController@viewMyCollection');
    Route::post('myprofile/mycollections/{id}/edit', 'CollectionController@editCollection');
    Route::get('myprofile/mycollections/{id}/ro-crate', 'CollectionController@downloadPrivateROCrate');
});

/**
 * Admin pages
 * The Admin Controller passes through 'auth' and 'verified' middleware for all functions AND checks user is admin
 * Each method manually checks for ADMIN/SUPER_ADMIN itself, will display 403 if not of sufficient role
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('admin', 'Auth\AdminController@adminHome'); //Only let ADMIN or SUPER_ADMIN access this page
    Route::get('admin/users', 'Auth\AdminController@userManagement'); //Only let SUPER_ADMIN access this page
    Route::get('admin/users/{id}', 'Auth\AdminController@viewUser'); //Only let  SUPER_ADMIN access this page
    Route::post('admin/users/{email}/activateDeactivateUser', 'Auth\AdminController@activateDeactivateUser'); //Only let SUPER_ADMIN access this page
    Route::post('admin/users/{email}/updateUserRole', 'Auth\AdminController@updateUserRole'); //Only let SUPER_ADMIN access this page
});

/**
 * AJAX functions
 */
Route::post('ajaxbbox', 'AjaxController@ajaxbbox'); //Does not need to be logged in

Route::middleware(['auth', 'verified'])->group(function () { //must be logged in for these
    Route::post('ajaxsavesearch', 'AjaxController@ajaxsavesearch');
    Route::post('ajaxsubsearch', 'AjaxController@ajaxsubsearch');
    Route::post('ajaxdeletesearch', 'AjaxController@ajaxdeletesearch');

    Route::get('ajaxviewdataitem', 'AjaxController@ajaxviewdataitem');
    Route::post('ajaxeditdataitem', 'AjaxController@ajaxeditdataitem');
    Route::post('ajaxadddataitem', 'AjaxController@ajaxadddataitem');
    Route::post('ajaxdeletedataitem', 'AjaxController@ajaxdeletedataitem');

    Route::post('ajaxdeletedataset', 'AjaxController@ajaxdeletedataset');

    Route::post('ajaxdestroysharelinks', 'AjaxController@ajaxdestroysharelinks');
    Route::post('ajaxgeneratesharelink', 'AjaxController@ajaxgeneratesharelink');
    Route::post('ajaxjoindataset', 'AjaxController@ajaxjoindataset');
    Route::post('ajaxleavedataset', 'AjaxController@ajaxleavedataset');
    Route::post('ajaxeditcollaborator', 'AjaxController@ajaxeditcollaborator');
    Route::post('ajaxdeletecollaborator', 'AjaxController@ajaxdeletecollaborator');

    Route::post('ajaxemailsharelink', 'AjaxController@ajaxemailsharelink');

    /**
     * Services for collection operations.
     */
    Route::post('ajaxdeletecollection', 'CollectionController@ajaxDeleteCollection');
    Route::post('ajaxremovecollectiondataset', 'CollectionController@ajaxRemoveCollectionDataset');
    Route::post('ajaxremovecollectionsavedsearch', 'CollectionController@ajaxRemoveCollectionSavedSearch');
    Route::post('ajaxaddcollectiondataset', 'CollectionController@ajaxAddCollectionDataset');

    /**
     * Services used for add collection datasets.
     */
    Route::get('ajax/collections/{collection_id}/datasets/addable/public', 'CollectionController@ajaxGetPublicDatasetOptions');
    Route::get('ajax/collections/{collection_id}/datasets/addable/user', 'CollectionController@ajaxGetUserDatasetOptions');
    Route::get('ajax/collections/{collection_id}/datasets/addable/{dataset_id}/info', 'CollectionController@ajaxGetDatasetInfo');

    /**
     * Services used for saved search to collection.
     */
    Route::get('ajax/saved-searches', 'CollectionController@ajaxGetUserSavedSearch')->name('ajax.saved-searches');
    Route::post('ajax/add-saved-search', 'CollectionController@ajaxAddSavedSearch')->name('ajax.add-saved-search');
});

/**
 * Authentication routes? (unsure what this is specifically)
 */
Auth::routes(['verify' => true]);
Route::get('verify', 'Auth\VerificationController@showPage');

/**
 * Output gaz as lpf
 */
Route::get('outputgazaslpf', 'LPFController@gazToLPF')->middleware('auth');
