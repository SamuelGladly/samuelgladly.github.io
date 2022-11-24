$(".album-poster").on('click', function (e) {
    var dataSwitchId = $(this).attr('data-switch');
    ap.list.switch(dataSwitchId);
    ap.play();
    $("#aplayer").addClass('showPlayer');
});

const ap = new APlayer({
    container: document.getElementById('aplayer'),
    listFolded: true,
    audio: [
        {
            name: 'Anti-Hero',
            artist: 'Taylor Swift',
            url: 'Songs/Anti-Hero - Taylor Swift.mp3',
            cover: 'Images/Anti-Hero - Taylor Swift.jpg'
        }, {
            name: 'Kesariya',
            artist: 'Amitabh Bhattacharya, Arijit Singh, Pritam',
            url: 'Songs/Kesariya - Brahmāstra.mp3',
            cover: 'Images/Kesariya - Brahmāstra.jpg'
        }, {
            name: 'Kho Gaye',
            artist: 'Taruk Raina',
            url: 'Songs/Kho Gaye - Mismatched Season 2.mp3',
            cover: 'Images/Kho Gaye - Mismatched Season 2.jpg'
        }, {
            name: 'Left And Right',
            artist: 'Charlie Puth & BTS Jungkook',
            url: 'Songs//Left And Right - Charlie Puth & BTS Jungkook.mp3',
            cover: 'Images/Left And Right - Charlie Puth & BTS Jungkook.jpg'
        }, {
            name: 'As It Was',
            artist: 'Harry Styles',
            url: 'Songs/As It Was - Harry Styles.mp3',
            cover: 'Images/As It Was - Harry Styles.jpg'
        }, {
            name: 'You',
            artist: 'Armaan Malik',
            url: 'Songs/You- Armaan Malik.mp3',
            cover: 'Images/You- Armaan Malik.jpg'
        }, {
            name: 'Kahani Song',
            artist: 'Amitabh Bhattacharya, Mohan Kannan, Pritam ',
            url: 'Songs/Kahani Song - Laal Singh Chaddha.mp3',
            cover: 'Images/Kahani Song - Laal Singh Chaddha.jpg'
        }, {
            name: 'Deva-Deva',
            artist: 'Amitabh Bhattacharya, Arijit Singh, Jonita Gandhi, Pritam',
            url: 'Songs/Deva-Deva - Brahmastra.mp3',
            cover: 'Images/Deva-Deva - Brahmastra.jpg'
        }, {
            name: 'Rangi Saari',
            artist: 'Kanishk Seth & Kavita Seth',
            url: 'Songs/Rangi Saari - Kanishk Seth & Kavita Seth.mp3',
            cover: 'Images/Rangi Saari - Kanishk Seth & Kavita Seth.jpg'
        }, {
            name: 'Golden hour',
            artist: 'JVKE',
            url: 'Songs/Golden hour - JVKE.mp3',
            cover: 'Images/Golden hour - JVKE.jpg'
        }, {
            name: 'Dandelions',
            artist: 'Ruth B.',
            url: 'Songs//Dandelions - Ruth B..mp3',
            cover: 'Images//Dandelions - Ruth B..jpg'
        }, {
            name: 'Manike',
            artist: 'Jubin Nautiyal, Rashmi Virag, Surya Ragunaathan, Tanishk Bagchi, Yohani ',
            url: 'Songs/Manike - Thank God.mp3',
            cover: 'Images/Manike - Thank God.jpg'
        }, ]
});
