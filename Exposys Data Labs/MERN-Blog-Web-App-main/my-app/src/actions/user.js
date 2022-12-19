import axios from "axios";



export const loginUser = (email, password, navigate) => async (dispatch) => {
    try {
        const {data} = await axios.post('/api/v1/user/login',
        {email, password},
        {
            headers: {
                "Content-Type": "application/json"
            }
        });
        dispatch({
            type: "LOGIN_SUCCESS",
            payload: data.user,
        });
        // window.alert(response.data.message);
        // console.log(data.success);
        // console.log(data.user);
        window.alert(data.message);

        navigate("/");
        // loadUser();
    } catch (error) {
        dispatch({
            type: "LOGIN_FAILURE",
            payload: error.message,
        });
        // console.log(error);
        // console.log(error.response.data.message);
        window.alert(error.response.data.message);
    }
}   

export const loadUser = () => async (dispatch) => {
    try {
        const {data} = await axios.get("/api/v1/user/me");
        dispatch({
            type: "USER_LOADED_SUCCESS",
            payload: data.user,
        });
        // console.log(response);
    } catch (error) {
        dispatch({
            type: "USER_LOADED_FAILURE",
            payload: error.response.data.message,
        });
        console.log(error.response.data.message);
    }
}

export const registerUser = (name, email, password, navigate) => async (dispatch) => {
    try {
        const {data} = await axios.post('/api/v1/user/register',
        {name, email, password},
        {
            headers: {
                "Content-Type": "application/json"
            }
        });
        dispatch({
            type: "REGISTER_SUCCESS",
            payload: data.user,
        });
        window.alert(data.message);
        navigate("/");
    } catch (error) {
        dispatch({
            type: "REGISTER_FAILURE",
            payload: error.message,
        });
        window.alert(error.response.data.message);
    }
}

// Logout User
export const logoutUser = () => async (dispatch) => {
    // try {
    //     await axios.post("/user/logout");
    //     dispatch({
    //         type: "LOGOUT_SUCCESS",
    //     });
    // } catch (error) {
    //     dispatch({
    //         type: "LOGOUT_FAILURE",
    //         payload: error.message,
    //     });
    // }
    try {

        const response = await axios.get("/api/v1/user/logout");
        dispatch({
            type: "LOGOUT_SUCCESS",
            
        });
        window.alert(response.data.message);

    }catch(error){
        dispatch({
            type: "LOGOUT_FAILURE",
            payload: error.message,
        });
    }
}

// get my posts
export const getMyPosts = () => async (dispatch) => {
    try {
        const {data} = await axios.get("/api/v1/user/my/posts");
        dispatch({
            type: "MYPOSTS_SUCCESS",
            payload: data.posts,
        });
    } catch (error) {
        dispatch({
            type: "MYPOSTS_FAILURE",
            payload: error.response.data.message,
        });
    }
}

// Get all user
export const getAllUsers = () => async (dispatch) => {
    try {
        const {data} = await axios.get("/api/v1/user/allusers");
        dispatch({
            type: "ALLUSERS_SUCCESS",
            payload: data.users,
        });
        // console.log(data.users);
    } catch (error) {
        dispatch({
            type: "ALLUSERS_FAILURE",
            payload: error.response.data.message,
        });
    }
}

// Get User Profile
export const getUserProfile = (id) => async (dispatch) => {
    // const id = 62fd1b1184d0fc8c923ce059
    try{
        const {data} = await axios.get(`/api/v1/user/${id}`);
        dispatch({
            type: "USERPROFILE_SUCCESS",
            payload: data.user,
        });

    }catch(error){
        dispatch({
            type: "USERPROFILE_FAILURE",
            payload: error.response.data.message,
        });
    }   
};

export const getUserPosts = (id) => async (dispatch) => {
    console.log("thi")
    try {
        const {data} = await axios.get(`/api/v1/user/userpost/${id}`);
        dispatch({
            type: "USERPOST_SUCCESS",
            payload: data.posts,
        });
        console.log(data);
    } catch (error) {
        dispatch({
            type: "USERPOST_FAILURE",
            payload: error.response.data.message,
        });
    }
}

export const updateProfile = (name, email, bio, profession, image, navigate) => async (dispatch) => {
    try {
        const {data} = await axios.put('/api/v1/user/update/profile',
        {name, email, bio, profession, image},
        {
            headers: {
                "Content-Type": "application/json"
            }
        });
        dispatch({
            type: "UPDATEPROFILE_SUCC",
            payload: data.user,
        });
        // window.alert(response.data.message);
        console.log(data);
        // console.log(data.user);
        window.alert(data.message);

        navigate("/profile");
        loadUser();
    } catch (error) {
        dispatch({
            type: "UPDATEPROFILE_FAIL",
            payload: error.response.data.message,
        });
        // console.log(error);
        // console.log(error.response.data.message);
        window.alert(error.response.data.message);
    }
}  

// Get my Saved Posts

export const getMySavedPosts = () => async (dispatch) => {
    try {
        const {data} = await axios.get("/api/v1/user/saved/posts");
        dispatch({
            type: "MYSAVEDPOST_SUCC",
            payload: data.posts,
        });
        // console.log(data.posts);
    } catch (error) {
        dispatch({
            type: "MYSAVEDPOST_FAIL",
            payload: error.response.data.message,
        });
    }
}

export const updatePassword = (oldPassword, newPassword, navigate) => async (dispatch) => {
    try {
        const {data} = await axios.put('/api/v1/user/update/password',
        {oldPassword, newPassword},
        {
            headers: {
                "Content-Type": "application/json"
            }
        });
        dispatch({
            type: "UPDATEPASSWORD_SUCC",
            payload: data.message,
        });
        // window.alert(response.data.message);
        // console.log(data.success);
        // console.log(data.user);
        // console.log(data);
        window.alert(data.message);

        navigate("/profile");
        // loadUser();
    } catch (error) {
        dispatch({
            type: "UPDATEPASSWORD_FAIL",
            payload: error.message,
        });
        // console.log(error);
        // console.log(error.response.data.message);
        window.alert(error.response.data.message);
    }
}  

export const forgotPassword = (email, navigate) => async (dispatch) => {
    try {
        const {data} = await axios.post('/api/v1/user/forgot/password',
        {email},
        {
            headers: {
                "Content-Type": "application/json"
            }
        });
        dispatch({
            type: "FORGOTPASS_SUCC",
            payload: data.message,
        });

        window.alert(data.message);
    } catch (error) {
        dispatch({
            type: "FORGOTPASS_FAIL",
            payload: error.message,
        });
        // console.log(error);
        // console.log(error.response.data.message);
        window.alert(error.response.data.message);
    }
}  


  export const resetPassword = (token, password, navigate) => async (dispatch) => {
    try {

       const { data } = await axios.put(
        `/api/v1/user/password/reset/${token}`,
        {
          password,
        },
        {
          headers: {
            "Content-Type": "application/json",
          },
        }
      );
  
      dispatch({
        type: "RESETPASS_SUCC",
        payload: data.message,
      });
      console.log(data);
      window.alert(data.message);
      navigate("/login");
    } catch (error) {
      dispatch({
        type: "RESETPASS_FAIL",
        payload: error.response.data.message,
      });
      window.alert(error.response.data.message);
    }
  };  


  export const googleAuth = (ress,navigate) => async (dispatch) => {
    try {
        const {data} = await axios.post('/api/v1/user/googleauth',{ress},
        {
                headers: {
                    "Content-Type": "application/json"
                }
        });
        dispatch({
            type : "GOOGLE_SUCC",
            payload : data.user,
        });
        window.alert(data.message);
        loadUser();
        navigate('/')
    }catch(error){
        dispatch({
            type: "GOOGLE_FAIL",
            payload: error.message,
        });
        window.alert(error.response.data.message);
    }
}



























