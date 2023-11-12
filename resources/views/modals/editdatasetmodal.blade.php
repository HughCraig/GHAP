@push('styles')
    <link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
    <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
    <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
    <!-- So we can quickly reuse the date regex check code -->
    <script src="{{ asset('/js/form.js') }}"></script>
    <script>
        const currentKeywords = {!! $ds->subjectkeywords !!};
    </script>
    <script src="{{ asset('/js/editdatasetmodal.js') }}"></script>
@endpush

<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editdatasetModal">Edit Layer</button>
<!-- MODAL popup -->
<div class="modal fade" id="editdatasetModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel">
                    Edit Layer
                    @include('templates.misc.contentdisclaimer')
                </h3>

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form method="POST" id="edit_dataset_form" action="{{url()->full()}}/edit" enctype="multipart/form-data">
                <div class="modal-body scrollable">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6">
                            Layer name<label class="text-danger">*</label>
                            <input type="text" class="mb-4 w3-white form-control" name="dsn" value="{{$ds->name}}" required/>

                            Subject (keywords)
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="Type and press enter to create keywords describing this layer."></span>
                            <input id="tags" name="tags" type="text" class="smallerinputs mb-4 w3-white form-control"/>

                            Record Type
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The type of information in this layer. If the type is mixed, use ‘other’."></span>
                            <select class="mb-4 w3-white form-control" id="recordtype" name="recordtype"
                                    value="{{$ds->recordtype->type}}" oldvalue="{{$ds->recordtype->type}}">>
                                @foreach($recordtypes as $type)
                                    @if($type == $ds->recordtype->type)
                                        <option label="{{$type}}" selected>{{$type}}</option>
                                    @else
                                        <option label="{{$type}}">{{$type}}</option> @endif
                                @endforeach
                            </select>

                            Visibility
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="TLCMap is intended for making information public, but you can set it to private while you work on it if you want. Some visualisations may not work while the layer is set to private."></span>
                            <select id="public" name="public" class="mb-4 w3-white form-control">
                                <option value="0"{{ $ds->public ? '' : ' selected' }}>Private</option>
                                <option value="1"{{ $ds->public ? ' selected' : '' }}>Public</option>
                            </select>

                            Allow ANPS to collect this data?
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="GHAP is based on information collected and compiled by the Australian National Placenames Survey, who keep records of historical and other placenames. If your layer includes placenames, we’d like to provide them back to ANPS to help their research and records."></span>
                            <select id="allowanps" name="allowanps" class="mb-4 w3-white form-control">
                                <option value="0"{{ $ds->allowanps ? '' : ' selected' }}>No</option>
                                <option value="1"{{ $ds->allowanps ? ' selected' : '' }}>Yes</option>
                            </select>

                            Creator
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The person or organisation who researched or prepared the data."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="creator" value="{{$ds->creator}}"/>

                            Publisher
                            <input type="text" class="mb-4 w3-white form-control" name="publisher" value="{{$ds->publisher}}"/>

                            Contact
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="Contact details if people have questions about this layer."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="contact" value="{{$ds->contact}}"/>

                            Language
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The language this layer is in. Use the two-digit language code where possible, such as ‘EN’ for English."></span>
                            <input type="text" class=" mb-4 w3-white form-control" name="language" value="{{$ds->language}}"/>

                            License
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The usage licence that applies to this layer. Open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created the information, you can choose the licence. If you obtained it from another source, select the licence specified there."></span>
                            <input type="text" class=" mb-4 w3-white form-control" name="license" value="{{$ds->license}}"/>

                            <p>Image</p>
                            @if( $ds->image_path )
                                <img src="{{ asset('storage/images/' . $ds->image_path) }}" alt="Layer Image" style="max-height: 150px;">
                            @endif
                            <input type="file" name="image" id="datasetEditImage" accept="image/*"/>

                        </div>
                        <div class="col-lg-6">
                            DOI
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="A valid Data Object Identifier for the ‘official’ version of the information in this layer. TLCMap can be used to visualise the information, but isn’t an official research archive or data repository. You can always add a DOI later. This layer will also receive a unique identifier and URL that can be used in citations, though it is not a DOI."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="doi" value="{{$ds->doi}}"/>

                            Source URL <!-- help hover button -->
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The URL linking to the source for the information in this layer. This should be the URL only."> </span>
                            <input type="text" class="mb-4 w3-white form-control" name="source_url" value="{{$ds->source_url}}"/>

                            Linkback
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The URL linking to the website for your project. This should be the URL only."> </span>
                            <input type="text" class="mb-4 w3-white form-control" name="linkback" value="{{$ds->linkback}}"/>

                            Spatial Coverage
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The latitude and longitude of the ‘bounding box’ for the area covered by this layer."></span>
                            <div class="border p-3 mb-4">
                                from latitude: <input type="text" class="mb-4 w3-white form-control" name="latitudefrom" value="{{$ds->latitude_from}}"/>
                                from longitude: <input type="text" class="mb-4 w3-white form-control" name="longitudefrom" value="{{$ds->longitude_from}}"/>
                                to latitude: <input type="text" class="mb-4 w3-white form-control" name="latitudeto" value="{{$ds->latitude_to}}"/>
                                to longitude: <input type="text" class="mb-4 w3-white form-control" name="longitudeto" value="{{$ds->longitude_to}}"/>
                            </div>

                            Temporal Coverage
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The date range covered by the information in this layer."></span>
                            <div class="border p-3 mb-4">
                                <div class="input-group date" id="temporalfromdiv">
                                    From: <input type="text" value="{{$ds->temporal_from}}"
                                                 class="mb-2 w3-white form-control input-group-addon"
                                                 name="temporalfrom" id="temporalfrom" autocomplete="off"/>
                                </div>
                                <div class="input-group date" id="temporaltodiv">
                                    To: <input type="text" value="{{$ds->temporal_to}}"
                                               class="mb-2 w3-white form-control input-group-addon" name="temporalto"
                                               id="temporalto" autocomplete="off">
                                </div>
                            </div>

                            Date Created
                            <span tabindex="0" data-html="true" data-animation="true"
                                  class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="The date that the information in this layer was created."></span>
                            <input type="date" class="mb-4 w3-white form-control" name="created" value="{{$ds->created}}"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="mb-4">
                                Description<label class="text-danger">*</label>
                                <span tabindex="0" data-html="true" data-animation="true"
                                      class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                      title="A short paragraph summarising the layer. Anything not covered by other fields can be added here."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor"
                                          name="description" id="description">{{$ds->description}}</textarea>
                            </div>
                            <div class="mb-4">
                                Content Warning
                                <span tabindex="0" data-html="true" data-animation="true"
                                      class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                      title="Anything the viewer should be aware of before viewing information in this layer, such as that the content may distress some viewers."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor" name="warning"
                                          id="warning">{{$ds->warning}}</textarea>
                            </div>
                            <div class="mb-4">
                                Citation
                                <span tabindex="0" data-html="true" data-animation="true"
                                      class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                      title="A bibliographic citation people should use when referencing this data, such as its source or related project."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor"
                                          name="citation">{{ $ds->citation }}</textarea>
                            </div>
                            <div class="mb-4">
                                Usage Rights
                                <span tabindex="0" data-html="true" data-animation="true"
                                      class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                      title="If not covered by the licence, the rights that apply to use of the information in this layer. You may need to declare that you use it with permission, and others would also have to ask before re-using it; or that it is out of copyright."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor"
                                          name="rights">{{ $ds->rights }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <span class="text-danger">* required fields</span>
                    <input class="btn btn-primary" type="submit" value="Save" name="Save" id="editDatasetSaveButton">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
