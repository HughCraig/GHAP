@extends('templates.layout')

@push('styles')
<link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
<link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
<script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
<script>
    const max_upload_image_size = {{config('app.max_upload_image_size')}};
    const allowed_text_file_types = @json(config('app.allowed_text_file_types'));
    const text_max_upload_file_size = {{config('app.text_max_upload_file_size')}}
</script>
<script src="{{ asset('js/usernewtext.js') }}"></script>
@endpush

@section('content')

<h2>
    {{ Auth::user()->name }}'s New Text
</h2>

<div class="container-fluid border">

    <form method="POST" id="new_text_form" action="{{url()->full()}}/create" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg p-5">

                *Upload
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="Upload a plain text file. If the text is in MS Word, or PDF, either save as 'text only' or copy and paste into a plain text editor. If there is a character encoding option, choose UTF-8. Short texts will run quickly. Novel length texts may take up to an hour to process."></span>
                <div class="d-flex">
                    <input type="file" name="textfile" id="textAddFile" class="mb-4" />
                </div>

                *Text Name
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="The name you want to use to refer to the text. This will be the title on resulting map."></span>
                <input type="text" class="mb-2 w3-white form-control" name="textname" required />

                Subject (keywords)
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="Type and press enter to create keywords describing this multilayer."></span>
                <input id="tags" name="tags" type="text" class="smallerinputs mb-2 w3-white form-control" />

                Text Type
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="Indicating if this is fiction or non fiction may help map users."></span>
                <select class="w3-white form-control mb-2" id="texttype" name="texttype">
                   <option value="" selected disabled>Select Type</option> <!-- Blank option as default -->
                    @foreach($texttypes as $type)
                    <option label="{{$type}}">{{$type}}</option>
                    @endforeach
                </select>

                Creator
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="The person or organisation who researched or prepared the data."></span>
                <input type="text" class="mb-2 w3-white form-control" name="creator" />

            </div>

            <div class="col-lg p-5">

                Publisher
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="Optionally name the publisher which may be different to the creator. You can also add a citation below."></span>
                <input type="text" class="mb-2 w3-white form-control" name="publisher" />

                Contact
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="Contact details if people have questions about this multilayer."></span>
                <input type="text" class="mb-2 w3-white form-control" name="contact" />

                DOI <!-- help hover button -->
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="A valid Data Object Identifier for the ‘official’ version of the information in this multilayer. TLCMap can be used to visualise the information, but isn’t an official research archive or data repository. You can always add a DOI later. This multilayer will also receive a unique identifier and URL that can be used in citations, though it is not a DOI."></span>
                <input type="text" class="mb-4 w3-white form-control" name="doi" />

                Source URL <!-- help hover button -->
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="The URL linking to the source for the information in this multilayer. This should be the URL only."> </span>
                <input type="text" class="mb-4 w3-white form-control" name="source_url" />

                Linkback
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                    title="The URL linking to the website for your project. This should be the URL only."> </span>
                <input type="text" class="mb-4 w3-white form-control" name="linkback" />

            </div>

            <div class="col-lg p-5">

                Language
                <span tabindex="0" data-bs-html="true" data-bs-animation="true"
                    class="bi bi-question-circle" data-bs-toggle="tooltip"
                    data-bs-placement="right"
                    title="The language this multilayer is in. Use the two-digit language code where possible, such as ‘EN’ for English."></span>
                <input type="text" class=" mb-2 w3-white form-control" name="language" />

                License
                <span tabindex="0" data-bs-html="true" data-bs-animation="true"
                    class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                    title="The usage licence that applies to this multilayer. Open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created the information, you can choose the licence. If you obtained it from another source, select the licence specified there."></span>
                <input type="text" class="mb-2 w3-white form-control" name="license" />

                Image
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                    title='Max upload size {{ floor(config("app.max_upload_image_size") / (1024 * 1024)) . " MB" }}'>
                </span>
                <input type="file" name="image" id="textAddImage" accept="image/*" />
            </div>

            <div class="col-lg p-5">
                Temporal Coverage
                <span tabindex="0" data-bs-html="true" data-bs-animation="true"
                    class="bi bi-question-circle" data-bs-toggle="tooltip"
                    data-bs-placement="right"
                    title="The date range covered by the information in this multilayer."></span>
                <div class="border p-3 mb-3">
                    <div class="input-group date" id="temporalfromdiv">
                        From: <input type="text" class="mb-2 w3-white form-control input-group-addon"
                            name="temporalfrom" id="temporalfrom" autocomplete="off" />
                    </div>
                    <div class="input-group date" id="temporaltodiv">
                        To: <input type="text" class="mb-2 w3-white form-control input-group-addon"
                            name="temporalto" id="temporalto" autocomplete="off">
                    </div>
                </div>

                Date Created
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                    data-bs-toggle="tooltip" data-bs-placement="right"
                    title="The date that the information in this multilayer was created."></span>
                <input type="date" class="mb-2 w3-white form-control" name="created" />
            </div>
        </div>

        <div class="row">
            <div class="col-lg p-5">
                <div class="mb-4">
                    *Description
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                        data-bs-toggle="tooltip" data-bs-placement="right"
                        title="A short paragraph summarising the multilayer. Anything not covered by other fields can be added here."></span>
                    <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" name="description"></textarea>
                </div>
                <div class="mb-4">
                    Citation <!-- help hover button -->
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                        data-bs-toggle="tooltip" data-bs-placement="right"
                        title="A bibliographic citation people should use when referencing this data, such as its source or related project."></span>
                    <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" name="citation"></textarea>
                </div>
            </div>
            <div class="col-lg p-5">
                <div class="mb-4">
                    Content Warning
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                        data-bs-toggle="tooltip" data-bs-placement="right"
                        title="Anything the viewer should be aware of before viewing information in this multilayer, such as that the content may distress some viewers."></span>
                    <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" name="warning"></textarea>
                </div>
                <div class="mb-4">
                    Usage Rights
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle"
                        data-bs-toggle="tooltip" data-bs-placement="right"
                        title="If not covered by the licence, the rights that apply to use of the information in this multilayer. You may need to declare that you use it with permission, and others would also have to ask before re-using it; or that it is out of copyright."></span>
                    <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" name="rights"></textarea>
                </div>
            </div>
        </div>
        <button class="m-4 p-4 btn btn-primary" type="Submit" id="addTextSaveButton">Create Text</button>
    </form>
   
   
</div>
<div class="mt-4 m-0 row"><a href="{{url('myprofile/mytexts')}}" class="mb-3 btn btn-primary">Back</a></div>
@endsection