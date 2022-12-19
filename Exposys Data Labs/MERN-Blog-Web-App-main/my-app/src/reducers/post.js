import { createReducer } from "@reduxjs/toolkit";

const initialState = {
    posts: [],
    loading : true,
};

export const postReducer = createReducer(initialState, {
    POST_SUCCESS: (state, action) => {
        state.loading = false;
        state.posts = action.payload;
    },
    POST_FAILURE: (state, action) => {
        state.loading = true;
        state.error = action.payload;
    },
    POST_CREATE_SUCCESS: (state, action) => {
        state.loading = false;
        state.message = action.payload;
    },
    POST_CREATE_FAILURE: (state, action) => {
        state.loading = true;
        state.error = action.payload;
    },
});
export const searchPostReducer = createReducer(initialState, {
    SEARCH_SUCCESS : (state, action) =>{
        state.loading = false;
        state.posts = action.payload;
    },
    SEARCH_FAILURE : (state, action) =>{
        state.loading = false;
        state.error = action.payload;
    },
});


export const trendingPostReducer = createReducer(initialState, {
    TREND_POST_SUCC: (state, action) => {
        state.loading = false;
        state.posts = action.payload;
    },
    TREND_POST_FAIL: (state, action) => {
        state.loading = true;
        state.error = action.payload;
    },
});

export const postDetailReducer = createReducer(initialState,{
    POST_DETAILS_SUCCESS : (state, action) =>{
        state.loading = false;
        state.posts = action.payload;
    },
    POST_DETAILS_FAILURE : (state, action) =>{
        state.loading = false;
        state.error = action.payload;
    },
}) 

export const myPostReducer = createReducer(initialState, {
    MYPOSTS_SUCCESS: (state, action) => {
        state.loading = false;
        state.posts = action.payload;
    },
    MYPOSTS_FAILURE: (state, action) => {
        state.loading = false;
        state.error = action.payload;
    },
});

export const tagReducer = createReducer(initialState, {
    TAGS_SUCCESS: (state, action) => {
        state.loading = false;
        state.posts = action.payload;
    },
    TAGS_FAILURE: (state, action) => {
        state.loading = false;
        state.error = action.payload;
    }
} );

export const userPostReducer = createReducer(initialState, {
    USERPOST_SUCCESS: (state, action) => {
        state.loading = false;
        state.posts = action.payload;
    },
    USERPOST_FAILURE: (state, action) => {
        state.loading = false;
        state.error = action.payload;
    },
});

export const mySavedPostReducer = createReducer(initialState, {
    MYSAVEDPOST_SUCC: (state, action) => {
        state.loading = false;
        state.posts = action.payload;
    },
    MYSAVEDPOST_FAIL: (state, action) => {
        state.loading = false;
        state.error = action.payload;
    },
} );






//     POST_COMMENT_REQUEST: (state) => {
//         state.loading = true;
//     }
//     POST_COMMENT_SUCCESS: (state, action) => {
//         state.loading = false;
//         state.comments = action.payload;
//     }
//     POST_COMMENT_FAILURE: (state, action) => {
//         state.loading = false;
//         state.error = action.payload;
//     }
//     POST_COMMENT_DELETE_REQUEST: (state) => {
//         state.loading = true;
//     }
//     POST_COMMENT_DELETE_SUCCESS: (state, action) => {
//         state.loading = false;
//         state.comments = action.payload;
//     }
//     POST_COMMENT_DELETE_FAILURE: (state, action) => {
//         state.loading = false;
//         state.error = action.payload;
//     }
//     POST_COMMENT_UPDATE_REQUEST: (state) => {
//         state.loading = true;
//     }
//     POST_COMMENT_UPDATE_SUCCESS: (state, action) => {
//         state.loading = false;
//         state.comments = action.payload;
//     }
//     POST_COMMENT_UPDATE_FAILURE: (state, action) => {
//         state.loading = false;
//         state.error = action.payload;
//     }
//     POST_COMMENT_CREATE_REQUEST: (state) => {
//         state.loading = true;
//     }
//     POST_COMMENT_CREATE_SUCCESS: (state, action) => {
//         state.loading = false;
//         state.comments = action.payload;
//     }
//     POST_COMMENT_CREATE_FAILURE: (state, action) => {
//         state.loading = false;
//         state.error = action.payload;
//     }
// }













