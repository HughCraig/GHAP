//Select option for role by current DB value
$("#selectRole option").each(function(){
    if($(this).val() === $(this).parent().data('role')){
        $(this).attr("selected","selected");
    }
});
