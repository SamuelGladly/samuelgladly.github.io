let box = document.getElementById("cards");

let API = async () => {
  let response = await fetch("https://reqres.in/api/users?page=1");
  let file = await response.json();
  let info = file.data
    .map((element) => {
      return `<div class="card">
        <div class="card-image">
            <img src="bg.jpg" alt="">
        </div>
        <div class="profile-image">
            <img src="${element.avatar}" alt="">
        </div>
        <div class="card-content">
            <h3>${element.first_name} ${element.last_name}</h3>
            <h2>${element.email}</h2>
            <h2>User ID: ${element.id}</h2>
        </div>
    </div>`;
    })
    .join("");
  cards.innerHTML = info;
};

let navBtn = document.getElementById("nav-btn");
navBtn.addEventListener("click", () => {
  cards.innerHTML = `<h1 class='load'>Scanning Users Data ........</h1>`;
  setTimeout(() => {
    API();
  }, 3000);
});