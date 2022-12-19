/* Authored by Boaz James Otieno */
/* full screen image slider */

var arr=['images/1.jpg"','images/2.jpg','images/3.jpg','images/4.jpg','images/5.jpg']; //an array of image sources
var pos=0; //initializes image position in the array
$(document).ready(function () {
    var interval=5000; //interval for slide
    var loaderHtml='';
    arr.forEach(function (src) {
        loaderHtml+='<img src="'+src+'">';
    });

    $('.load-images').html(loaderHtml);

    var htm='';
    /* initializes the small circles html*/
    for(var i=0;i<arr.length;i++){
        htm+='<div id="'+i+'" class="circle" onclick="circleClick('+i+')"> </div> ';

    }

    $('#circles').html(htm);//show small circles
    $('#slider').html('<img src="'+arr[0]+'" class="img-slide image-animated"">');//show first image
    $('#0').css({'background':'#fff', 'color':'#fff'});//sets the background of the first small circle to black


    /* Auto slides the images with the image sources array given as first argument and interval as second argument */
    function autoSlide(arr,interval){

        setInterval(function () {
            $('.img-slide').css({'opacity':'.1 !important'});
            pos++;
            if(pos>arr.length-1){
                pos=0;
            }

            $('#slider').html('<img src="'+arr[pos]+'" class="img-slide img'+pos+' image-animated">');//shows image
            $('#'+pos).css({'background':'#fff', 'color':'#fff'});//sets background-color of circle representing the current active image to black
            $('#'+(pos-1)).css({'background':'transparent', 'color':'transparent'});//sets background-color of circle before active to white
            if(pos==0){
                $('#'+(arr.length-1)).css({'background':'transparent', 'color':'transparent'});
            }

        },interval);
    }
    /* end of function autoSlide */

    autoSlide(arr,interval);//calls function autoSlide

    /* displays next image */
    function next(){
        if(pos>arr.length-2){
            pos=-1;
        }
        $('#slider').html('<img src="'+arr[pos+1]+'" class="img-slide image-animated">');//show image
        pos++;

        $('#'+pos).css({'background':'#fff', 'color':'#fff'});//sets background-color of circle representing the current active image to black
        $('#'+(pos-1)).css({'background':'transparent', 'color':'transparent'});//sets background-color of circle before active to white
        if(pos==0){
            $('#'+(arr.length-1)).css({'background':'transparent', 'color':'transparent'});
        }
    }
    /* end of function next  */

    /* displays previous image */
    function previous() {
        if(pos<1){
            pos=arr.length;
        }
        $('#slider').html('<img src="'+arr[pos-1]+'" class="img-slide image-animated">');
        pos--;

        $('#'+pos).css({'background':'#fff', 'color':'#fff'});//sets background-color of circle representing the current active image to black
        $('#'+(pos+1)).css({'background':'transparent', 'color':'transparent'});//sets background-color of circle before active to white
        if(pos==arr.length-1){
            $('#0').css({'background':'transparent', 'color':'transparent'});
        }
    }
    /* end of function previous */

    /* onclick next */
    $('button#next').on('click',function (e) {
        e.preventDefault();
        next();//call function next
    });
    /* end of onclick next */

    /* onclick previous */
    $('button#prev').on('click',function (e) {
        e.preventDefault();
        previous();//call function previous
    });
    /* end of onclick previous */

});

/* displays image represented by the small circle */
function circleClick(position) {
    if(position!=pos){
        $('#slider').html('<img src="'+arr[position]+'" class="img-slide image-animated">');//show image

        $('#'+position).css({'background':'#fff', 'color':'#fff'});//sets background-color of circle representing the current active image to black
        $('#'+(pos)).css({'background':'transparent', 'color':'transparent'});//sets background-color of circle before active to white

        pos=position;
    }
    /* end of function circleClick */


}
