const $ = jQuery;

$(function(){
    console.log("DOM ready");
    $(".sort-options-button").click(function(event){
       if(event.target.value === 'pending'){
        $('.col-1').css("display", "none");
        $('.col-0').css("display", "table-row");
       }
       else{
        $('.col-0').css("display", "none");
        $('.col-1').css("display", "table-row");
       } 
    });

    $("#approve-button").click(function(event){
        let nid = event.target.value;
        $.get('/featured_works/update?nid=' + nid, function(data){
            console.log("Event updated");
        });
    });

});