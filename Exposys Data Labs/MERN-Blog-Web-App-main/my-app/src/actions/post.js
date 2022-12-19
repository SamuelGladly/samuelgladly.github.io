import axios from 'axios';



// Get all the post
export const getPosts = () => async (dispatch) => {
    try {
        const response = await axios.get('/api/v1/posts');
        dispatch({
            type: "POST_SUCCESS",
            payload: response.data,
        });
    }catch(error){
        dispatch({
            type: "POST_FAILURE",
            payload: error.message,
        });
    }
}

// Get Trending Posts
export const getTrendingPosts = () => async (dispatch) => {
    try {
        const response = await axios.get('/api/v1/posts/trending/post');
        dispatch({
            type: "TREND_POST_SUCC",
            payload: response.data,
        });
    }catch(error){
        dispatch({
            type: "TREND_POST_FAIL",
            payload: error.message,
        });
    }
}

// Create Post
export const createPost = (title,shortDescription, message, category, tags, image, navigate) => async (dispatch) => {
    
    try {
        const {data} = await axios.post('/api/v1/posts', {title,shortDescription, message, category, tags, image});
        dispatch({
            type: "POST_CREATE_SUCCESS",
            payload: data.message,
        });
        window.alert(data.message);
        navigate('/profile')
        // console.log(response.data);
    }catch(error){
        dispatch({
            type: "POST_CREATE_FAILURE",
            payload: error.message,
        });
        window.alert(error.response.data.message);
        console.log(error);
    }
}


// Get Post by search
export const getPostsBySearch = (search) => async (dispatch) => {
    try {
        const response = await axios.get(`/api/v1/posts/search?searchQuery=${search}`);
        dispatch({
            type: "SEARCH_SUCCESS",
            payload: response.data,
        });
    }catch(error){
        dispatch({
            type: "SEARCH_FAILURE",
            payload: error.message,
        });
    }
}


// Get Post by Id
export const getPostDetails = (id) => async (dispatch) => {
    try {
        const response = await axios.get(`/api/v1/posts/${id}`);
        dispatch({
            type: "POST_DETAILS_SUCCESS",
            payload: response.data,
        });
    }catch(error){
        dispatch({
            type: "POST_DETAILS_FAILURE",
            payload: error.message,
        });
    }
}

// Get Post by Tags
export const getPostsByTags = (tag) => async (dispatch) => {
    try {
        const response = await axios.get(`/api/v1/posts/tag/${tag}`);
        dispatch({
            type: "TAGS_SUCCESS",
            payload: response.data,
        });
    }catch(error){
        dispatch({
            type: "TAGS_FAILURE",
            payload: error.message,
        });
    }
}

// Add comment

export const addComment =(name, comment,id) => async (dispatch) => {
    try{
        const {data} = await axios.post(`/api/v1/posts/${id}/comment`,
        {
            name,
            comment
        },
        {
            headers : {
                "Content-Type": "application/json",
            },
        }
    );
    dispatch({
        type : "COMMENT_SUCCESS",
        payload : data.message
    });
    window.alert(data.message);
    
    }catch(error){
        dispatch({
            type :"COMMENT_FAILURE",
            payload:error.message,
        })

    }
} 

export const savePost = (id) => async (dispatch) => {
    try {
        const {data} = await axios.post("/api/v1/posts/save",
            {
                id
            },
            {
                headers : {
                    "Content-Type": "application/json",
                },
            
        });
        dispatch({
            type: "SAVE_SUCCESS",
            payload: data.message,
        });
        window.alert(data.message);
    } catch (error) {
        dispatch({
            type: "SAVE_FAILURE",
            payload: error.message,
        });
    }
}

// Update Post
export const updatePost = (id,title,message,tags,category,image,navigate ) => async (dispatch) => {
    try {
        const {data} = await axios.patch(`/api/v1/posts/${id}`,
            {
                title,message,tags,category,image
            },
            {
                headers : {
                    "Content-Type": "application/json",
                },
            }
        );
        dispatch({
            type : "UPDATEPOST_SUCC",
            payload : data.message
        });
        window.alert(data.message);
        navigate('/');
    }catch(error) {
        dispatch({
            type :"UPDATEPOST_FAIL",
            payload:error.message,
        })
    }
}

export const deletePost = (id) => async (dispatch) => {
    try {
        const {data} = await axios.delete(`/api/v1/posts/${id}`);
        dispatch({
            type: "DELETEPOST_SUCC",
            payload: data.message,
          });
          window.alert(data.message);
          getPosts();
    }catch(error) {
        dispatch({
            type :"DELETEPOST_FAIL",
            payload:error.message,
        })
    }
}
























