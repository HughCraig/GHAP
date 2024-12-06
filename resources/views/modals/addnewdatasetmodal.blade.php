<div class="modal fade" id="newLayerModal" tabindex="-1" role="dialog" aria-labelledby="newLayerModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    New Layer
                </h3>
                <button type="button" class="close add_layer_button_back" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body scrollable">
                <p>You can edit information about this layer later under 'My Layers'.</p>
                <div class="scrollable">
                    <div class="message-banner"></div>

                    Layer name <label class="text-danger">*</label>
                    <input type="text" class="mb-2 w3-white form-control" id="layername" required />

                    <div class="mb-4">
                        Description <label class="text-danger">*</label>
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                            title="A short paragraph summarising the layer. Anything not covered by other fields can be added here."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" id="description"></textarea>
                    </div>
                    
                    Subject (keywords)
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="Type and press enter to create keywords describing this layer."></span>
                    <input id="tags" name="tags" type="text" class="smallerinputs mb-2 w3-white form-control" />

                    <label for="layerrecordtype">Record Type</label>
                    <select class="w3-white form-control mb-3" id="layerrecordtype" name="addrecordtype">
                        @foreach($recordtypes as $recordtype)
                        <option label="{{$recordtype->type}}">{{$recordtype->type}}</option>
                        @endforeach
                    </select>

                    Visibility
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="TLCMap is intended for making information public, but you can set it to private while you work on it if you want. Some visualisations may not work while the layer is set to private."></span>
                    <select id="public" name="public" class="mb-4 w3-white form-control">
                        <option value="0">Private</option>
                        <option value="1" selected="selected">Public</option>
                    </select>

                    Allow ANPS to collect this data?
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="GHAP is based on information collected and compiled by the Australian National Placenames Survey, who keep records of historical and other placenames. If your layer includes placenames, we’d like to provide them back to ANPS to help their research and records."></span>
                    <select id="allowanps" name="allowanps" class="mb-4 w3-white form-control">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>

                    Creator
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The person or organisation who researched or prepared the data."></span>
                    <input type="text" class="mb-2 w3-white form-control" id="creator" />

                    Publisher
                    <input type="text" class="mb-2 w3-white form-control" id="publisher" />

                    Contact
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="Contact details if people have questions about this layer."></span>
                    <input type="text" class="mb-2 w3-white form-control" id="contact" />


                    DOI <!-- help hover button -->
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="A valid Data Object Identifier for the ‘official’ version of the information in this layer. TLCMap can be used to visualise the information, but isn’t an official research archive or data repository. You can always add a DOI later. This layer will also receive a unique identifier and URL that can be used in citations, though it is not a DOI."></span>
                    <input type="text" class="mb-4 w3-white form-control" id="doi" />

                    Source URL <!-- help hover button -->
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The URL linking to the source for the information in this layer. This should be the URL only."> </span>
                    <input type="text" class="mb-4 w3-white form-control" id="source_url" />

                    Linkback
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The URL linking to the website for your project. This should be the URL only."> </span>
                    <input type="text" class="mb-4 w3-white form-control" id="linkback" />

                    Language
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The language this layer is in. Use the two-digit language code where possible, such as ‘EN’ for English."></span>
                    <input type="text" class=" mb-2 w3-white form-control" id="language" />


                    Spatial Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The latitude and longitude of the ‘bounding box’ for the area covered by this layer."></span>
                    <div class="border p-3 mb-3">
                        from latitude: <input type="text" class="mb-2 w3-white form-control" id="latitudefrom" />
                        from longitude: <input type="text" class="mb-2 w3-white form-control" id="longitudefrom" />
                        to latitude: <input type="text" class="mb-2 w3-white form-control" id="latitudeto" />
                        to longitude: <input type="text" class="mb-2 w3-white form-control" id="longitudeto" />
                    </div>

                    License
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The usage licence that applies to this layer. Open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created the information, you can choose the licence. If you obtained it from another source,  the licence specified there."></span>
                    <input type="text" class="mb-2 w3-white form-control" id="license" />

                    Image
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title='Max upload size {{ floor(config("app.max_upload_image_size") / (1024 * 1024)) . " MB" }}'>
                    </span>
                    <input class="mb-3" type="file" name="image" id="datasetAddImage" accept="image/*" />

                    Temporal Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The date range covered by the information in this layer."></span>
                    <div class="border p-3 mb-3">
                        <div class="input-group date" id="temporalfromdiv">
                            From: <input type="text" class="mb-2 w3-white form-control input-group-addon" id="temporalfrom" autocomplete="off" />
                        </div>
                        <div class="input-group date" id="temporaltodiv">
                            To: <input type="text" class="mb-2 w3-white form-control input-group-addon"  id="temporalto" autocomplete="off">
                        </div>
                    </div>

                    Date Created
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The date that the information in this layer was created."></span>
                    <input type="date" class="mb-2 w3-white form-control" id="created" />

                    <div class="mb-4">
                        Citation <!-- help hover button -->
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                            title="A bibliographic citation people should use when referencing this data, such as its source or related project."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" id="citation"></textarea>
                    </div>

                    <div class="mb-4">
                        Content Warning
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                            title="Anything the viewer should be aware of before viewing information in this layer, such as that the content may distress some viewers."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" id="warning"></textarea>
                    </div>
                    <div class="mb-4">
                        Usage Rights
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                            title="If not covered by the licence, the rights that apply to use of the information in this layer. You may need to declare that you use it with permission, and others would also have to ask before re-using it; or that it is out of copyright."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" id="rights"></textarea>
                    </div>


                </div>
            </div>
            <div class="modal-footer">
                <span class="text-danger">* required fields</span>
                <button type="button" class="btn btn-primary" id="add_layer_button_submit">Create layer</button>
                <button type="button" class="btn btn-secondary add_layer_button_back">Back</button>
            </div>
        </div>
    </div>
</div>