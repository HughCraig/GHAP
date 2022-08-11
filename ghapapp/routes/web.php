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
//Route::any('/', function () {return redirect('');});
Route::any('/home', function () {
    return redirect('');
})->name('home');
Route::any('', 'HomeController@index')->name('index');
Route::any('file', 'GazetteerController@searchFromFile')->name('searchFromFile'); //search from file
Route::any('kmlpolygonsearch', 'GazetteerController@searchFromKmlPolygon')->name('searchFromKmlPolygon'); //search from file
Route::any('places/{id?}', 'GazetteerController@search')->name('places'); //shows places with optional id, if no id is given it uses all results before applying filters
Route::any('search', 'GazetteerController@search')->name('search')->middleware('checkmaxpaging');
Route::any('maxpaging', 'GazetteerController@maxPagingMessage')->name('maxPagingMessage');
Route::any('maxpagingredirect', 'GazetteerController@maxPagingRedirect')->name('maxPagingRedirect');
Route::post('bulkfileparser', 'GazetteerController@bulkFileParser');

/**
 * Public dataset pages
 */
Route::any('publicdatasets', 'DatasetController@viewPublicDatasets')->name('publicdatasets');
Route::any('publicdatasets/{id}', 'DatasetController@viewPublicDataset')->name('publicdataset');
Route::any('publicdatasets/{id}/kml', 'DatasetController@viewPublicKML')->name('viewpublicdatasetkml');
Route::any('publicdatasets/{id}/kml/download', 'DatasetController@downloadPublicKML')->name('downloadpublicdatasetkml');
Route::any('publicdatasets/{id}/json', 'DatasetController@viewPublicJSON')->name('viewpublicdatasetjson');
Route::any('publicdatasets/{id}/json/download', 'DatasetController@downloadPublicJSON')->name('downloadpublicdatasetjson');
Route::any('publicdatasets/{id}/csv', 'DatasetController@viewPublicCSV')->name('viewpublicdatasetcsv');
Route::any('publicdatasets/{id}/csv/download', 'DatasetController@downloadPublicCSV')->name('downloadpublicdatasetcsv');

/**
 * Public collection pages.
 */
Route::get('publiccollections', 'CollectionController@viewPublicCollections');
Route::get('publiccollections/{id}', 'CollectionController@viewPublicCollection');
Route::get('publiccollections/{id}/json', 'CollectionController@viewPublicJson');

/**
 * User Pages.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::any('myprofile', 'User\UserController@userProfile')->name('myProfile');
    Route::any('myprofile/mydatasets', 'User\UserController@userDatasets')->name('myDatasets'); //Only let users view own dataset
    Route::any('myprofile/mysearches', 'User\UserController@userSavedSearches')->name('mySearches');
    Route::any('myprofile/mysearches/delete', 'User\UserController@deleteUserSavedSearches');
    Route::any('myprofile/mydatasets/newdataset', 'User\UserController@newDatasetPage');
    Route::any('myprofile/mydatasets/newdataset/create', 'User\UserController@createNewDataset');
    Route::any('myprofile/mydatasets/{id}', 'User\UserController@userViewDataset'); //Only let users view own dataset
    Route::any('myprofile/mydatasets/{id}/collaborators', 'User\UserController@userEditCollaborators');
    Route::any('myprofile/mydatasets/{id}/collaborators/destroysharelink', 'User\UserController@userDestroyShareLink');
    Route::post('bulkadddataitem', 'User\UserController@bulkAddDataItem'); //not ajax as it is too much data
    Route::any('myprofile/mydatasets/{id}/edit', 'User\UserController@userEditDataset');
    Route::any('myprofile/edit', 'User\UserController@editUserPage')->name('editUserPage');
    Route::any('myprofile/edit/info', 'User\UserController@editUserInfo')->name('editUserInfo');
    Route::any('myprofile/edit/password', 'User\UserController@editUserPassword')->name('editUserPassword');
    Route::any('myprofile/edit/email', 'User\UserController@editUserEmail')->name('editUserEmail');
    Route::any('myprofile/mydatasets/join/{link?}', 'AjaxController@ajaxjoindataset'); //Join a dataset by link

    Route::any('myprofile/mydatasets/{id}/kml', 'DatasetController@viewPrivateKML')->name('viewdatasetkml');
    Route::any('myprofile/mydatasets/{id}/kml/download', 'DatasetController@downloadPrivateKML')->name('downloaddatasetkml');
    Route::any('myprofile/mydatasets/{id}/json', 'DatasetController@viewPrivateJSON')->name('viewdatasetjson');
    Route::any('myprofile/mydatasets/{id}/json/download', 'DatasetController@downloadPrivateJSON')->name('downloaddatasetjson');
    Route::any('myprofile/mydatasets/{id}/csv', 'DatasetController@viewPrivateCSV')->name('viewdatasetcsv');
    Route::any('myprofile/mydatasets/{id}/csv/download', 'DatasetController@downloadPrivateCSV')->name('downloaddatasetcsv');
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
});


/**
 * Admin pages
 * The Admin Controller passes through 'auth' and 'verified' middleware for all functions AND checks user is admin
 * Each method manually checks for ADMIN/SUPER_ADMIN itself, will display 403 if not of sufficient role
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::any('admin', 'Auth\AdminController@adminHome'); //Only let ADMIN or SUPER_ADMIN access this page
    Route::any('admin/users', 'Auth\AdminController@userManagement'); //Only let SUPER_ADMIN access this page
    Route::any('admin/users/{email}', 'Auth\AdminController@viewUser'); //Only let  SUPER_ADMIN access this page
    Route::any('admin/users/{email}/activateDeactivateUser', 'Auth\AdminController@activateDeactivateUser'); //Only let SUPER_ADMIN access this page
    Route::any('admin/users/{email}/updateUserRole', 'Auth\AdminController@updateUserRole'); //Only let SUPER_ADMIN access this page
});

/**
 * Misc Functions
 */
Route::any('places/download/', 'GazetteerController@getDownload');


/**
 * AJAX functions
 */
Route::post('ajaxbbox', 'AjaxController@ajaxbbox'); //Does not need to be logged in

Route::middleware(['auth', 'verified'])->group(function () { //must be logged in for these
    Route::post('ajaxsavesearch', 'AjaxController@ajaxsavesearch');
    Route::post('ajaxsubsearch', 'AjaxController@ajaxsubsearch');
    Route::post('ajaxdeletesearch', 'AjaxController@ajaxdeletesearch');

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
    Route::post('ajaxaddcollectiondataset', 'CollectionController@ajaxAddCollectionDataset');

    /**
     * Services used for add collection datasets.
     */
    Route::get('ajax/collections/{collection_id}/datasets/addable/public', 'CollectionController@ajaxGetPublicDatasetOptions');
    Route::get('ajax/collections/{collection_id}/datasets/addable/user', 'CollectionController@ajaxGetUserDatasetOptions');
    Route::get('ajax/collections/{collection_id}/datasets/addable/{dataset_id}/info', 'CollectionController@ajaxGetDatasetInfo');
});


/**
 * Authentication routes? (unsure what this is specifically)
 */
//Auth::routes();
Auth::routes(['verify' => true]);
Route::get('verify', 'Auth\VerificationController@showPage');


/**
 * Output gaz as lpf
 */
Route::get('outputgazaslpf', 'LPFController@gazToLPF')->middleware('auth');
