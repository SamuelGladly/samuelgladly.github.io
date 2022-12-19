
// import express from 'express';
// import bodyParser from 'body-parser';
// import mongoose from 'mongoose';
// import cors from 'cors';
const dotenv = require('dotenv');
const express = require('express');
const bodyParser = require('body-parser');
const mongoose = require('mongoose');
const cors = require("cors");
const cookieParser = require("cookie-parser");
const connectDatabase = require("./db/database");
const cloudinary = require('cloudinary');


dotenv.config({ path: '.env' });
connectDatabase();
const app = express();

cloudinary.config({
  cloud_name: process.env.CLOUDINARY_NAME,
  api_key: process.env.CLOUDINARY_API_KEY,
  api_secret: process.env.CLOUDINARY_API_SECRET,
});

// import postRoutes from './routes/posts.js';
const postRoutes = require('./routes/posts');
const userRoutes = require('./routes/user');
// const userRoute = require('./routes/userRoute');
app.use(cookieParser())


app.use(bodyParser.json({ limit: '50mb', extended: true }))
app.use(bodyParser.urlencoded({ limit: '50mb', extended: true }))
app.use(express.json());
app.use(cors());

app.use('/api/v1/posts', postRoutes);
app.use("/api/v1/user", userRoutes)
// mongodb://localhost:27017/simple

// console.log(process.env.BASE_URL);

const PORT = process.env.PORT|| 5000;

app.listen(PORT, () => {
  console.log(`Server running on ${PORT}`);
});

// mongoose.connect(procee.env.CONNECTION_URL, { useNewUrlParser: true, useUnifiedTopology: true })
//   .then(() => 
  
//   app.listen(PORT, () => console.log(`Server Running on Port: http://localhost:${PORT}`)))

//   .catch((error) => console.log(`${error} did not connect`));

// mongoose.set('useFindAndModify', false);


// CONNECTION_URL = 'mongodb://localhost:27017/memories'