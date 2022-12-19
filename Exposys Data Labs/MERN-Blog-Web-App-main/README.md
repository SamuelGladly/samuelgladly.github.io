
# MERN Blog Web App

![Screenshot (277)](https://user-images.githubusercontent.com/47392217/192132793-e13bdbc6-a144-44f3-b2fb-80ecf4c6267d.png)

## Introduction :
Website link : https://theblognotes.herokuapp.com/

This is a Guest blog webiste, made on top of mern stack.

Anyone can make account on the website and write a blog post.

### Some of the Features of the website :

#### User Funcations  : 

- Register or Login with Email
- Register or Login with Google
- Register or Login Facebook
- Update Password
- Forgot Password
- Reset Password
- Update Password
- Save Blog 
- Get Any User Profile

#### Blogs Funcations :
- Create Blog
- Update Blog
- Delete Blog 
- Comment on Blog
- Get Blog Details

## Technology Used :

### Frontend :
React, React-redux, React-hooks 

### Backend :
Node, Express

### Database : 
Mongodb (mongodb Atlas - for deployment)

### Depolyment :
Heroku 

### To send reset-mails :
Nodemailer

## How to use this project :

#### Step 1 : Download the zip file 
```
```
#### Step 2 : UnZip the folder and open the folder in VS Code or any editor

#### Step 3 : Open a terminal and split it in to two (in vs code)
One terminal is for client and one for server respectively.

#### Step 4 : Inside both the terminal run the following commands :
```
npm install
```
#### Step 5 : Go inside the ```package.json``` of client folder and chnage `proxy` to localhost 
```
"proxy": "http://localhost:5000"
```
Note : The above step is necessary to run the server locally.

#### Step 6 : Add `.env` file inside the server folder , and add these environment variable in it :
```
CONNECTION_URL = ''

CLOUDINARY_NAME=""
CLOUDINARY_API_KEY=""
CLOUDINARY_API_SECRET=""


SMPT_SERVICE='gmail'
SMPT_HOST='smtp.gmail.com'
SMPT_PORT=465
SMPT_MAIL=""
SMPT_PASSWORD=''


BASE_URL='http://localhost:3000'

```
#### Step 7 : Add Mongoose Url to connect to database

- For Database, hosted locally : ```mongodb://localhost:27017/blogdb```
  blogdb - is the name of the database
- For Databse, hosted on clouf : Get the url, and add it to .env file

#### Step 8 : Folder Structure :
Server Folder Structure :

![Screenshot (276)](https://user-images.githubusercontent.com/47392217/192132530-7d91d352-e2f4-4271-a6c0-3b8b53eb705a.png)

Client Folder Structure :

![Screenshot (275)](https://user-images.githubusercontent.com/47392217/192132563-6aa2481c-7062-429b-bbb2-79310f73e60e.png)

#### Step 9 : Run the following commands to execute the codes :
- Inside client folder terminal : ```npm start```
- Inside server folder terminal : ```npm run dev```

