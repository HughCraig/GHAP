@push('styles')
<link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
<link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
<script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
<!-- So we can quickly reuse the date regex check code -->
<script>
    const currentKeywords = {!!$text->subjectKeywords!!};
</script>
<script>
    const max_upload_image_size = {{config('app.max_upload_image_size')}};
</script>
<script src="{{ asset('/js/edittextmodal.js') }}"></script>
@endpush

<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#edittextModal">Edit Text</button>
<!-- MODAL popup -->
<div class="modal fade" id="edittextMo
dal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel">
                    Edit Text
                    @include('templates.misc.contentdisclaimer')
                </h3>

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form method="POST" id="edit_text_form" action="{{url()->full()}}/edit" enctype="multipart/form-data">
                <div class="modal-body scrollable">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6">
                            Text Name<label class="text-danger">*</label>
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign"
                            data-toggle="tooltip" data-placement="right"
                            title="The name you want to use to refer to the text. This will be the title on resulting map."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="textname" value="{{$text->name}}" required />

                            Subject (keywords)
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="Type and press enter to create keywords describing this text."></span>
                            <input id="tags" name="tags" type="text" class="smallerinputs mb-4 w3-white form-control" />


                            Text Type
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign"
                                data-toggle="tooltip" data-placement="right"
                                title="Indicating if this is fiction or non fiction may help map users."></span>
                            <select class="mb-4 w3-white form-control" id="texttype" name="texttype">
                                <option value="" @if(is_null($text->texttype) && old('texttype') == '') selected @endif>N/A</option> <!-- N/A option selected by default if no value -->
                                @foreach($texttypes as $type)
                                    <option label="{{$type}}" value="{{$type}}" 
                                        @if(($text->texttype && $text->texttype->type == $type) || old('texttype') == $type)
                                            selected
                                        @endif
                                    >
                                        {{$type}}
                                    </option>
                                @endforeach
                            </select>

                            Creator
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="The person or organisation who researched or prepared the data."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="creator" value="{{$text->creator}}" />

                            Publisher
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign"
                            data-toggle="tooltip" data-placement="right"
                            title="Optionally name the publisher which may be different to the creator. You can also add a citation below."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="publisher" value="{{$text->publisher}}" />

                            Contact
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="Contact details if people have questions about this text."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="contact" value="{{$text->contact}}" />

                            Image
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title='Max upload size {{ floor(config("app.max_upload_image_size") / (1024 * 1024)) . " MB" }}'>
                            </span>
                            @if($text->image_path)
                            <img src="{{ asset('storage/images/' . $text->image_path) }}" alt="Layer Image" style="max-height: 150px;">
                            <div class="pt-4 pb-2">
                                <input type="checkbox" id="textDeleteImage" name="text_delete_image">
                                <label class="pr-4" for="textDeleteImage">Delete current image</label>
                            </div>
                            @endif
                            <input type="file" name="image" id="textEditImage" accept="image/*" />

                        </div>
                        <div class="col-lg-6">

                            Language
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="The language this text is in. Use the two-digit language code where possible, such as ‘EN’ for English."></span>
                            <input type="text" class=" mb-4 w3-white form-control" name="language" value="{{$text->language}}" />


                            License
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="The usage licence that applies to this text. Open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created the information, you can choose the licence. If you obtained it from another source, select the licence specified there."></span>
                            <input type="text" class=" mb-4 w3-white form-control" name="license" value="{{$text->license}}" />


                            DOI
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="A valid Data Object Identifier for the ‘official’ version of the information in this text. TLCMap can be used to visualise the information, but isn’t an official research archive or data repository. You can always add a DOI later. This text will also receive a unique identifier and URL that can be used in citations, though it is not a DOI."></span>
                            <input type="text" class="mb-4 w3-white form-control" name="doi" value="{{$text->doi}}" />

                            Source URL <!-- help hover button -->
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="The URL linking to the source for the information in this text. This should be the URL only."> </span>
                            <input type="text" class="mb-4 w3-white form-control" name="source_url" value="{{$text->source_url}}" />

                            Linkback
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="The URL linking to the website for your project. This should be the URL only."> </span>
                            <input type="text" class="mb-4 w3-white form-control" name="linkback" value="{{$text->linkback}}" />

                            Temporal Coverage
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="The date range covered by the information in this layer."></span>
                            <div class="border p-3 mb-4">
                                <div class="input-group date" id="temporalfromdiv">
                                    From: <input type="text" value="{{$text->temporal_from}}"
                                        class="mb-2 w3-white form-control input-group-addon"
                                        name="temporalfrom" id="temporalfrom" autocomplete="off" />
                                </div>
                                <div class="input-group date" id="temporaltodiv">
                                    To: <input type="text" value="{{$text->temporal_to}}"
                                        class="mb-2 w3-white form-control input-group-addon" name="temporalto"
                                        id="temporalto" autocomplete="off">
                                </div>
                            </div>

                            Date Created
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="The date that the information in this text was created."></span>
                            <input type="date" class="mb-4 w3-white form-control" name="created" value="{{$text->created}}" />
                        </div>
                    </div>
                    <div class="row pt-4">
                        <div class="col">
                            <div class="mb-4">
                                Description<label class="text-danger">*</label>
                                <span tabindex="0" data-html="true" data-animation="true"
                                    class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                    title="A short paragraph summarising the text. Anything not covered by other fields can be added here."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor"
                                    name="description" id="description">{{$text->description}}</textarea>
                            </div>
                            <div class="mb-4">
                                Content Warning
                                <span tabindex="0" data-html="true" data-animation="true"
                                    class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                    title="Anything the viewer should be aware of before viewing information in this text, such as that the content may distress some viewers."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor" name="warning"
                                    id="warning">{{$text->warning}}</textarea>
                            </div>
                            <div class="mb-4">
                                Citation
                                <span tabindex="0" data-html="true" data-animation="true"
                                    class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                    title="A bibliographic citation people should use when referencing this data, such as its source or related project."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor"
                                    name="citation">{{ $text->citation }}</textarea>
                            </div>
                            <div class="mb-4">
                                Usage Rights
                                <span tabindex="0" data-html="true" data-animation="true"
                                    class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                    title="If not covered by the licence, the rights that apply to use of the information in this text. You may need to declare that you use it with permission, and others would also have to ask before re-using it; or that it is out of copyright."></span>
                                <textarea rows="3" maxlength="1500" class="w-100 mb-4 w3-white form-control wysiwyg-editor"
                                    name="rights">{{ $text->rights }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <span class="text-danger">* required fields</span>
                    <input class="btn btn-primary" type="submit" value="Save" name="Save" id="editTextSaveButton">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>