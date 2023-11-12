@push('styles')
    <link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
    <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
    <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
    <script>
        const currentKeywords = {!! $collection->subjectKeywords !!};
    </script>
    <script src="{{ asset('/js/editcollectionmodal.js') }}"></script>
@endpush

<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editCollectionModal">Edit Multilayer</button>
<!-- MODAL popup -->
<div class="modal fade" id="editCollectionModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel">
                    Edit Multilayer
                    @include('templates.misc.contentdisclaimer', ['infoType' => 'multilayer'])
                </h3>

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form method="POST" id="edit_dataset_form" action="{{url()->full()}}/edit" enctype="multipart/form-data">
                <div class="modal-body">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6">
                            Multilayer name<label class="text-danger">*</label>
                            <input type="text" class="mb-4 w3-white form-control" name="name" value="{{$collection->name}}" required/>
                            
                            Subject (keywords)
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="Type and press enter to create keywords describing this multilayer."></span>
                            <input id="tags" name="tags" type="text" class="smallerinputs mb-4 w3-white form-control"/>

                            Visibility
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="TLCMap is intended for making information public, but you can set it to private while you work on it if you want. Some visualisations may not work while the multilayer is set to private."></span>
                            <select id="public" name="public" class="mb-4 w3-white form-control">
                                <option value="0"{{ $collection->public ? '' : ' selected' }}>Private</option>
                                <option value="1"{{ $collection->public ? ' selected' : '' }}>Public</option>
                            </select>

                            Creator
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The person or organisation who researched or prepared the data."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="creator"
                                   value="{{$collection->creator}}"/>

                            Publisher
                            <input type="text" class="mb-4 w3-white form-control" name="publisher"
                                            value="{{$collection->publisher}}"/>

                            Contact
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="Contact details if people have questions about this multilayer."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="contact"
                                   value="{{$collection->contact}}"/>

                            Language
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The language this multilayer is in. Use the two-digit language code where possible, such as ‘EN’ for English."></span>
                            <input type="text" class=" mb-4 w3-white form-control" name="language"
                                   value="{{$collection->language}}"/>

                            License
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The usage licence that applies to this multilayer. Open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created the information, you can choose the licence. If you obtained it from another source, select the licence specified there."></span>
                            <input type="text" class=" mb-4 w3-white form-control" name="license"
                                   value="{{$collection->license}}"/>
                            
                            <p>Image</p>
                            @if( $collection->image_path )
                                <img src="{{ asset('storage/images/' . $collection->image_path) }}" alt="Collection Image" style="max-height: 150px;">
                            @endif
                            <input type="file" name="image" id="collectionEditImage" accept="image/*"/>

                            DOI
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="A valid Data Object Identifier for the ‘official’ version of the information in this multilayer. TLCMap can be used to visualise the information, but isn’t an official research archive or data repository. You can always add a DOI later. This multilayer will also receive a unique identifier and URL that can be used in citations, though it is not a DOI."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="doi" value="{{$collection->doi}}"/>

                            Source URL <!-- help hover button -->
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The URL linking to the source for the information in this multilayer. This should be the URL only."> </span>
                            <input type="text" class="mb-4 w3-white form-control" name="source_url"
                                   value="{{$collection->source_url}}"/>
                        </div>

                        <div class="col-lg-6">
                            Linkback
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The URL linking to the website for your project. This should be the URL only."> </span>
                            <input type="text" class="mb-4 w3-white form-control" name="linkback" value="{{$collection->linkback}}"/>

                            Spatial Coverage
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The latitude and longitude of the ‘bounding box’ for the area covered by this multilayer."></span>
                            <div class="border p-3 mb-4">
                                from latitude: <input type="text" class="mb-4 w3-white form-control" name="latitudefrom"
                                                      value="{{$collection->latitude_from}}"/>
                                from longitude: <input type="text" class="mb-4 w3-white form-control"
                                                       name="longitudefrom" value="{{$collection->longitude_from}}"/>
                                to latitude: <input type="text" class="mb-4 w3-white form-control" name="latitudeto"
                                                    value="{{$collection->latitude_to}}"/>
                                to longitude: <input type="text" class="mb-4 w3-white form-control" name="longitudeto"
                                                     value="{{$collection->longitude_to}}"/>
                            </div>

                            Temporal Coverage
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The date range covered by the information in this multilayer."></span>

                            <div class="border p-3 mb-4">
                                <div class="input-group date" id="temporalfromdiv">
                                    From: <input type="text" value="{{$collection->temporal_from}}"
                                                 class="mb-2 w3-white form-control input-group-addon"
                                                 name="temporalfrom" id="temporalfrom" autocomplete="off"/>
                                </div>
                                <div class="input-group date" id="temporaltodiv">
                                    To: <input type="text" value="{{$collection->temporal_to}}"
                                               class="mb-2 w3-white form-control input-group-addon" name="temporalto"
                                               id="temporalto" autocomplete="off">
                                </div>
                            </div>

                            Date Created
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The date that the information in this multilayer was created."></span>
                            <input type="date" class="mb-4 w3-white form-control" name="created"
                                   value="{{$collection->created}}"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="mb-4">
                                Description<label class="text-danger">*</label>
                                <span tabindex="0" data-html="true" data-animation="true"
                                      class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                      title="A short paragraph summarising the multilayer. Anything not covered by other fields can be added here."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor"
                                          name="description" id="description">{{$collection->description}}</textarea>
                            </div>
                            <div class="mb-4">
                                Content Warning
                                <span tabindex="0" data-html="true" data-animation="true"
                                      class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                      title="Anything the viewer should be aware of before viewing information in this multilayer, such as that the content may distress some viewers."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor" name="warning"
                                          id="warning">{{$collection->warning}}</textarea>
                            </div>
                            <div class="mb-4">
                                Citation
                                <span tabindex="0" data-html="true" data-animation="true"
                                      class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                      title="A bibliographic citation people should use when referencing this data, such as its source or related project."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor" name="citation">{{$collection->citation}}</textarea>
                            </div>
                            <div class="mb-4">
                                Usage Rights
                                <span tabindex="0" data-html="true" data-animation="true"
                                      class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                      title="If not covered by the licence, the rights that apply to use of the information in this multilayer. You may need to declare that you use it with permission, and others would also have to ask before re-using it; or that it is out of copyright."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor" name="rights">{{$collection->rights}}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <span class="text-danger">* required fields</span>
                    <input class="btn btn-primary" type="submit" value="Save" name="Save" id="editCollectionSaveButton">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
