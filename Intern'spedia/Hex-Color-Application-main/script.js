document.querySelector("#submit").addEventListener("click", () => {
    var hexNum = ["0","1","2","3","4","5","6","7","8","9","A", "B", "C", "D", "E", "F"];
    var hex1 = "";
    var hex2 = "";
    var IndexRandom = 0;
    
    for(let i = 0; i < 6;i++){
      IndexRandom = Math.floor(Math.random() * hexNum.length);
        hex1 += hexNum[IndexRandom];
      IndexRandom = Math.floor(Math.random() * hexNum.length);
        hex2 += hexNum[IndexRandom];
    }
  
    document.body.style.background = `linear-gradient(#${hex1}, #${hex2})`;
    document.querySelector("#hex1").textContent = hex1;
    document.querySelector("#hex2").textContent = hex2;
    });