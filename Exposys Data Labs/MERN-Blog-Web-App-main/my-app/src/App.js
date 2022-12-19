

import Posts from "./Components/Posts/Posts";
import Form from "./Components/Form/Form";
import { useEffect } from "react";
import { useDispatch, useSelector } from 'react-redux';
import { getPosts } from "./actions/post";
import Login from "./Components/Login/Login";
import Home from "./Components/Home/Home";
import Navbar from "./Components/Navbar/Navbar";

import {BrowserRouter as Router, Routes, Route, Navigate} from "react-router-dom";
import Register from "./Components/Register/Register";
import PostDetails from "./Components/postDetails/Postdetails";
import Profile from "./Components/Profile/Profile";
import { loadUser } from "./actions/user";
import UserProfile from "./Components/UserProfile/UserProfile";
import Update from "./Components/Update/Update";
import SavedPosts from "./Components/SavedPosts/SavedPosts";
import NotFound from "./Components/NotFound";
import UpdatePassword from "./Components/UpdatePassword/UpdatePassword";
import ForgotPassword from "./Components/ForgotPassword/ForgotPassword";
import ResetPassword from "./Components/ResetPassword/ResetPassword";
import UpdatePost from "./Components/UpdatePost/UpdatePost";
import Testt from "./Components/Testt/Testt";
import SearchPosts from "./Components/SearchPosts/SearchPosts";
import TagPosts from "./Components/TagPosts/TagPosts";

// import AllUser from "./Components/AllUsers/AllUser";
import {gapi} from 'gapi-script';

const GOOGLE_CLIENT_ID ="877839825734-gm5817fj03oamdkm6b9th73obcsngv7e.apps.googleusercontent.com";




function App() {
  const dispatch = useDispatch();
  useEffect(() => {
    dispatch(loadUser());
  } , [dispatch]);

  // const r= useSelector((state) => state.user);
  // console.log(r)
  // console.log(r.isAuthenticated);
  // console.log(isAuthenticated);
  useEffect(() => {
    function start() {
      gapi.client.init({
        clientId: GOOGLE_CLIENT_ID,
        scope : " "
      })
    };
    gapi.load('client:auth2', start);
  });




  const {isAuthenticated} = useSelector((state) => state.user);
  // console.log(isAuthenticated);


  return (
    <div>
        <Testt/>
      <Routes>

      {/* <Route exact path="/" element={<Navigate to="/posts" />} />
      <Route exact path="/posts" element={<Home />} /> */}
      <Route exact path="/" element={<Home />} />

      <Route exact path="/login" element={isAuthenticated ? <Profile /> : <Login />} />
      <Route exact path="/register" element={isAuthenticated ? <Profile /> : <Register />} />

      
      <Route exact path="/user/:id" element={ <UserProfile />} />
      <Route exact path="/posts/:id" element={<PostDetails />} />

      <Route exact path="/search/posts" element = {<SearchPosts />}/>
      <Route exact path="/tag/posts" element = {<TagPosts/>}/>
      
          <Route exact path="/form" element={isAuthenticated ? <Form /> : <Login /> } />


          {/* <Route exact path="/profile" element={<Profile />} /> */} {/* It give error
          (not able to load data in the profile, (only loads data for first time), once the page is 
          refreshed it is not able to load the data) */}
          <Route exact path="/profile" element={ isAuthenticated ? <Profile /> : <Login />} />
          <Route exact path="/profile/update" element={ isAuthenticated ? <Update /> : <Login />} />

          <Route exact path="/saved/post" element={ isAuthenticated ? <SavedPosts /> : <Login />} />
          <Route exact path="/update/post/:id" element={ isAuthenticated ? <UpdatePost /> : <Login /> } />
          

          <Route exact path="/update/password" element={ isAuthenticated ? <UpdatePassword /> : <Login />} />
          <Route exact path="/forgot/password" element={isAuthenticated ? <UpdatePassword /> : <ForgotPassword />} />
          <Route exact path="/password/reset/:token" element={isAuthenticated ? <UpdatePassword /> : <ResetPassword />} />
          
          <Route path="*" element={<NotFound />} />
          {/* <Route exact path="/nav" element={} /> */}
      </Routes>
    </div>
  );
}

export default App;

// moment is a library for displaying dates and times.
// react-file-base64 is a library for uploading files to the server.
// redux-thunk is used for asynchronous actions using redux

