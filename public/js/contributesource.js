$(document).ready(function ($) {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $("#csrfToken").val(),
        },
    });

    window.contributesourcedata = null;
    window.fromTextID = null;

    const modalContent = $("#modal-content");
    const saveButton = $("#sourcesavebtn");

    function updateModalContent(selectedValue) {
        let content = "";

        if (selectedValue === "text") {
            $("#sourceguide").hide();
            content = `
                <label>Text name</label>
                <input type="text" class="form-control" name="text_name" />
                <label>Description</label>
                <input type="text" class="form-control" name="description" />
                <label>Paste text file</label>
                <input type="file" class="form-control" name="file" id="fileInput" />
                <label>Or</label>
                <textarea id="textAreaInput"   class="form-control" name="sourceContent" style="width:100% ; height:200px"></textarea>
            `;
        } else if (selectedValue === "kml") {
            $("#sourceguide").show();
            content = `
                <label>Paste KML file</label>
                <input type="file" class="form-control" name="file" id="fileInput" />
                <label>Or</label>
                <textarea class="form-control" name="sourceContent" id="textAreaInput" style="width:100%; height:200px"></textarea>
            `;
        } else if (selectedValue === "json") {
            $("#sourceguide").show();
            content = `
                <label>Paste JSON file</label>
                <input type="file" class="form-control" name="file" id="fileInput" />
            `;
        } else if (selectedValue === "csv") {
            $("#sourceguide").show();
            content = `
                <label>Paste CSV file</label>
                <input type="file" class="form-control" name="file" id="fileInput" />
            `;
        }

        modalContent.html(content);

        $("#fileInput").on("change", function () {
            if (this.files.length > 0) {
                $("#textAreaInput").prop("disabled", true);
            } else {
                $("#textAreaInput").prop("disabled", false);
            }
        });

        $("#textAreaInput").on("input", function () {
            if ($(this).val().trim() !== "") {
                $("#fileInput").prop("disabled", true);
            } else {
                $("#fileInput").prop("disabled", false);
            }
        });
    }

    $("input[name='source']").change(function () {
        updateModalContent($(this).val());
    });

    saveButton.click(function () {
        let selectedType = $("input[name='source']:checked").val();

        if (selectedType === "text") {
            let formData = new FormData();
            formData.append("textname", $("input[name='text_name']").val());
            formData.append("redirect", "false");
            formData.append(
                "description",
                $("input[name='description']").val()
            );
            formData.append(
                "_token",
                $('meta[name="csrf-token"]').attr("content")
            );

            let fileInput = $("#fileInput")[0].files[0];
            if (fileInput.size > text_max_upload_file_size) {
                alert(
                    "We are currently limiting individual uploads to " +
                        Math.floor(text_max_upload_file_size / (1024 * 1024)) +
                        " MB , in order to conserve system resources and ensure availability. Please consider breaking you text into sections."
                );
                return false;
            }

            let textContent = $("#textAreaInput").val().trim();

            if (fileInput) {
                formData.append("textfile", fileInput);
            } else if (textContent) {
                formData.append("textcontent", textContent);
            }

            $.ajax({
                url: "myprofile/mytexts/newtext/create",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $("#contributesource").modal("hide");
                    $("#parsetextID").val(response.id);
                    $("#parsetextSize").val(fileInput.size / 1024);
                    $("#userparsetextmodal").modal("show");
                },
            });
        } else {
            let form = $("<form>", {
                method: "POST",
                action: "usercontributeparsesource",
                enctype: "multipart/form-data",
                style: "display: none;",
            });

            form.append("@csrf");
            form.append(
                $("<input>", {
                    type: "hidden",
                    name: "type",
                    value: $("input[name='source']:checked").val(),
                })
            );

            modalContent.find("input, textarea").each(function () {
                if ($(this).attr("type") === "file") {
                    if (this.files.length > 0) {
                        form.append($(this).clone());
                    }
                } else {
                    form.append(
                        $("<input>", {
                            type: "hidden",
                            name: $(this).attr("name"),
                            value: $(this).val(),
                        })
                    );
                }
            });

            $("body").append(form);
            form.appendTo("body");
            let formData = new FormData(form[0]);

            $.ajax({
                url: form.attr("action"),
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    window.contributesourcedata = response;
                    $("#sourceadded").text(response.length + " places added to layer from source");
                    window.fromTextID = null;
                    $("#contributesource").modal("hide");
                },
                error: function (xhr) {
                    console.error("Error:", xhr.responseText);
                },
            });
        }
    });
});
