function up1(max) {
    
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber1").value = parseInt(document.getElementById("myNumber1").value) + 1;
    if (document.getElementById("myNumber1").value > parseInt(max)) {
        document.getElementById("myNumber1").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 10;
    }
       
    bill.innerText = cost;
    var quantity = parseInt(document.getElementById("tomato").innerText);
    if(parseInt(document.getElementById("myNumber1").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    tomato.innerText = quantity;
    
    
    pay.innerText ="Rs."  + cost + "/-";
    
}
function down1(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber1").value = parseInt(document.getElementById("myNumber1").value) - 1;
    if (document.getElementById("myNumber1").value < parseInt(min)) {
        document.getElementById("myNumber1").value = min;
        cost = cost - 0;
        
    } else {
        cost = cost - 10;
    }
    
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("tomato").innerText);
    if(parseInt(document.getElementById("myNumber1").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    tomato.innerText = quantity;
    
    
    pay.innerText = "Rs."  + cost + "/-";
   
}

function up2(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber2").value = parseInt(document.getElementById("myNumber2").value) + 1;
    if (document.getElementById("myNumber2").value > parseInt(max)) {
        document.getElementById("myNumber2").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 8;
    }
    bill.innerText = cost;  
    
    var quantity = parseInt(document.getElementById("spinach").innerText);
    if(parseInt(document.getElementById("myNumber2").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    spinach.innerText = quantity;
    
    
    pay.innerText = "Rs."  + cost + "/-";
    
}
function down2(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber2").value = parseInt(document.getElementById("myNumber2").value) - 1;
    if (document.getElementById("myNumber2").value < parseInt(min)) {
        document.getElementById("myNumber2").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 8;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("spinach").innerText);
    if(parseInt(document.getElementById("myNumber2").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    spinach.innerText = quantity;
    
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up3(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber3").value = parseInt(document.getElementById("myNumber3").value) + 1;
    if (document.getElementById("myNumber3").value > parseInt(max)) {
        document.getElementById("myNumber3").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 12;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("cabbage").innerText);
    if(parseInt(document.getElementById("myNumber3").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    cabbage.innerText = quantity;
    
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down3(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber3").value = parseInt(document.getElementById("myNumber3").value) - 1;
    if (document.getElementById("myNumber3").value < parseInt(min)) {
        document.getElementById("myNumber3").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 12;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("cabbage").innerText);
    if(parseInt(document.getElementById("myNumber3").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    cabbage.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up4(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber4").value = parseInt(document.getElementById("myNumber4").value) + 1;
    if (document.getElementById("myNumber4").value > parseInt(max)) {
        document.getElementById("myNumber4").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 15;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("onions").innerText);
    if(parseInt(document.getElementById("myNumber4").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    onions.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down4(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber4").value = parseInt(document.getElementById("myNumber4").value) - 1;
    if (document.getElementById("myNumber4").value < parseInt(min)) {
        document.getElementById("myNumber4").value = min;
        cost =  cost - 0;
    } else {
        cost = cost - 15;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("onions").innerText);
    if(parseInt(document.getElementById("myNumber4").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    onions.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up5(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber5").value = parseInt(document.getElementById("myNumber5").value) + 1;
    if (document.getElementById("myNumber5").value > parseInt(max)) {
        document.getElementById("myNumber5").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 23;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("jalapeno").innerText);
    if(parseInt(document.getElementById("myNumber5").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    jalapeno.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down5(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber5").value = parseInt(document.getElementById("myNumber5").value) - 1;
    if (document.getElementById("myNumber5").value < parseInt(min)) {
        document.getElementById("myNumber5").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 23;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("jalapeno").innerText);
    if(parseInt(document.getElementById("myNumber5").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    jalapeno.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up6(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber6").value = parseInt(document.getElementById("myNumber6").value) + 1;
    if (document.getElementById("myNumber6").value > parseInt(max)) {
        document.getElementById("myNumber6").value = max;
        cost = cost + 0;
    } else {
       cost = cost + 18; 
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("leafLettuce").innerText);
    if(parseInt(document.getElementById("myNumber6").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    leafLettuce.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down6(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber6").value = parseInt(document.getElementById("myNumber6").value) - 1;
    if (document.getElementById("myNumber6").value < parseInt(min)) {
        document.getElementById("myNumber6").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 18;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("leafLettuce").innerText);
    if(parseInt(document.getElementById("myNumber6").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    leafLettuce.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up7(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber7").value = parseInt(document.getElementById("myNumber7").value) + 1;
    if (document.getElementById("myNumber7").value > parseInt(max)) {
        document.getElementById("myNumber7").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 16;
    }
    bill.innerText = cost;  
    
    var quantity = parseInt(document.getElementById("mushroom").innerText);
    if(parseInt(document.getElementById("myNumber7").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    mushroom.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down7(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber7").value = parseInt(document.getElementById("myNumber7").value) - 1;
    if (document.getElementById("myNumber7").value < parseInt(min)) {
        document.getElementById("myNumber7").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 16;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("mushroom").innerText);
    if(parseInt(document.getElementById("myNumber7").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    mushroom.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up8(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber8").value = parseInt(document.getElementById("myNumber8").value) + 1;
    if (document.getElementById("myNumber8").value > parseInt(max)) {
        document.getElementById("myNumber8").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 8;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("cucumber").innerText);
    if(parseInt(document.getElementById("myNumber8").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    cucumber.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down8(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber8").value = parseInt(document.getElementById("myNumber8").value) - 1;
    if (document.getElementById("myNumber8").value < parseInt(min)) {
        document.getElementById("myNumber8").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 8;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("cucumber").innerText);
    if(parseInt(document.getElementById("myNumber8").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    cucumber.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up9(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber9").value = parseInt(document.getElementById("myNumber9").value) + 1;
    if (document.getElementById("myNumber9").value > parseInt(max)) {
        document.getElementById("myNumber9").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 30;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("cheddar").innerText);
    if(parseInt(document.getElementById("myNumber9").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    cheddar.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down9(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber9").value = parseInt(document.getElementById("myNumber9").value) - 1;
    if (document.getElementById("myNumber9").value < parseInt(min)) {
        document.getElementById("myNumber9").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 30;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("cheddar").innerText);
    if(parseInt(document.getElementById("myNumber9").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    cheddar.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up10(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber10").value = parseInt(document.getElementById("myNumber10").value) + 1;
    if (document.getElementById("myNumber10").value > parseInt(max)) {
        document.getElementById("myNumber10").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 35;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("mozarella").innerText);
    if(parseInt(document.getElementById("myNumber10").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    mozarella.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down10(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber10").value = parseInt(document.getElementById("myNumber10").value) - 1;
    if (document.getElementById("myNumber10").value < parseInt(min)) {
        document.getElementById("myNumber10").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 35;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("mozarella").innerText);
    if(parseInt(document.getElementById("myNumber10").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    mozarella.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up11(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber11").value = parseInt(document.getElementById("myNumber11").value) + 1;
    if (document.getElementById("myNumber11").value > parseInt(max)) {
        document.getElementById("myNumber11").value = max;
        cost = cost + 0;
    } else {
         cost = cost + 38;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("parmesan").innerText);
    if(parseInt(document.getElementById("myNumber11").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    parmesan.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down11(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber11").value = parseInt(document.getElementById("myNumber11").value) - 1;
    if (document.getElementById("myNumber11").value < parseInt(min)) {
        document.getElementById("myNumber11").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 38;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("parmesan").innerText);
    if(parseInt(document.getElementById("myNumber11").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    parmesan.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up12(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber12").value = parseInt(document.getElementById("myNumber12").value) + 1;
    if (document.getElementById("myNumber12").value > parseInt(max)) {
        document.getElementById("myNumber12").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 70; 
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("chicken").innerText);
    if(parseInt(document.getElementById("myNumber12").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    chicken.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down12(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber12").value = parseInt(document.getElementById("myNumber12").value) - 1;
    if (document.getElementById("myNumber12").value < parseInt(min)) {
        document.getElementById("myNumber12").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 70;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("chicken").innerText);
    if(parseInt(document.getElementById("myNumber12").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    chicken.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up13(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber13").value = parseInt(document.getElementById("myNumber13").value) + 1;
    if (document.getElementById("myNumber13").value > parseInt(max)) {
        document.getElementById("myNumber13").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 90;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("pork").innerText);
    if(parseInt(document.getElementById("myNumber13").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    pork.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down13(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber13").value = parseInt(document.getElementById("myNumber13").value) - 1;
    if (document.getElementById("myNumber13").value < parseInt(min)) {
        document.getElementById("myNumber13").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 90;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("pork").innerText);
    if(parseInt(document.getElementById("myNumber13").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    pork.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up14(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber14").value = parseInt(document.getElementById("myNumber14").value) + 1;
    if (document.getElementById("myNumber14").value > parseInt(max)) {
        document.getElementById("myNumber14").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 100;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("bacon").innerText);
    if(parseInt(document.getElementById("myNumber14").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    bacon.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down14(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber14").value = parseInt(document.getElementById("myNumber14").value) - 1;
    if (document.getElementById("myNumber14").value < parseInt(min)) {
        document.getElementById("myNumber14").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 100;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("bacon").innerText);
    if(parseInt(document.getElementById("myNumber14").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    bacon.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up15(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber15").value = parseInt(document.getElementById("myNumber15").value) + 1;
    if (document.getElementById("myNumber15").value > parseInt(max)) {
        document.getElementById("myNumber15").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 80;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("turkey").innerText);
    if(parseInt(document.getElementById("myNumber15").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    turkey.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down15(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber15").value = parseInt(document.getElementById("myNumber15").value) - 1;
    if (document.getElementById("myNumber15").value < parseInt(min)) {
        document.getElementById("myNumber15").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 80;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("turkey").innerText);
    if(parseInt(document.getElementById("myNumber15").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    turkey.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up16(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber16").value = parseInt(document.getElementById("myNumber16").value) + 1;
    if (document.getElementById("myNumber16").value > parseInt(max)) {
        document.getElementById("myNumber16").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 28;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("plain").innerText);
    if(parseInt(document.getElementById("myNumber16").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    plain.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down16(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber16").value = parseInt(document.getElementById("myNumber16").value) - 1;
    if (document.getElementById("myNumber16").value < parseInt(min)) {
        document.getElementById("myNumber16").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 28;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("plain").innerText);
    if(parseInt(document.getElementById("myNumber16").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    plain.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up17(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber17").value = parseInt(document.getElementById("myNumber17").value) + 1;
    if (document.getElementById("myNumber17").value > parseInt(max)) {
        document.getElementById("myNumber17").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 30;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("sesame").innerText);
    if(parseInt(document.getElementById("myNumber17").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    sesame.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down17(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber17").value = parseInt(document.getElementById("myNumber17").value) - 1;
    if (document.getElementById("myNumber17").value < parseInt(min)) {
        document.getElementById("myNumber17").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 30;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("sesame").innerText);
    if(parseInt(document.getElementById("myNumber17").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    sesame.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up18(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber18").value = parseInt(document.getElementById("myNumber18").value) + 1;
    if (document.getElementById("myNumber18").value > parseInt(max)) {
        document.getElementById("myNumber18").value = max;
        cost =cost + 0;
    } else {
        cost = cost + 35;
    }
    bill.innerText = cost;
     var quantity = parseInt(document.getElementById("engMuffin").innerText);
    if(parseInt(document.getElementById("myNumber18").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    engMuffin.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down18(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber18").value = parseInt(document.getElementById("myNumber18").value) - 1;
    if (document.getElementById("myNumber18").value < parseInt(min)) {
        document.getElementById("myNumber18").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 35;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("engMuffin").innerText);
    if(parseInt(document.getElementById("myNumber18").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    engMuffin.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up19(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber19").value = parseInt(document.getElementById("myNumber19").value) + 1;
    if (document.getElementById("myNumber19").value > parseInt(max)) {
        document.getElementById("myNumber19").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 40;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("pretzel").innerText);
    if(parseInt(document.getElementById("myNumber19").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    pretzel.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down19(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber19").value = parseInt(document.getElementById("myNumber19").value) - 1;
    if (document.getElementById("myNumber19").value < parseInt(min)) {
        document.getElementById("myNumber19").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 40;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("pretzel").innerText);
    if(parseInt(document.getElementById("myNumber19").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    pretzel.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}

function up20(max) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber20").value = parseInt(document.getElementById("myNumber20").value) + 1;
    if (document.getElementById("myNumber20").value > parseInt(max)) {
        document.getElementById("myNumber20").value = max;
        cost = cost + 0;
    } else {
        cost = cost + 20;   
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("mayo").innerText);
    if(parseInt(document.getElementById("myNumber20").value) === 10) {
        quantity = 10;
    } else {
        quantity = quantity + 1;
    }
    
    mayo.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}
function down20(min) {
    var cost = parseFloat(document.getElementById("bill").innerText);
    document.getElementById("myNumber20").value = parseInt(document.getElementById("myNumber20").value) - 1;
    if (document.getElementById("myNumber20").value < parseInt(min)) {
        document.getElementById("myNumber20").value = min;
        cost = cost - 0;
    } else {
        cost = cost - 20;
    }
    bill.innerText = cost;
    
    var quantity = parseInt(document.getElementById("mayo").innerText);
    if(parseInt(document.getElementById("myNumber20").value) === 0) {
        quantity = 0;
    } else {
        quantity = quantity - 1;
    }
    
    mayo.innerText = quantity;
    
    pay.innerText = "Rs."  + cost + "/-";
}