import { createReducer } from "@reduxjs/toolkit";

const initialState = {
    // isAuthenticated : false,
    // loading : true,
};

export const userReducer = createReducer(initialState, {

    LOGIN_SUCCESS: (state, action) => {
        state.isAuthenticated = true;
        state.user = action.payload;
        state.loading = false;
    },
    LOGIN_FAILURE: (state, action) => {
        state.isAuthenticated = false;
        state.loading = false;
        state.error = action.payload;
    },
    REGISTER_SUCCESS: (state, action) => {
        state.isAuthenticated = true;
        state.user = action.payload;
        state.loading = false;
    },
    REGISTER_FAILURE: (state, action) => {
        state.isAuthenticated = false;
        state.loading = false;
        state.error = action.payload;
    },
    USER_LOADED_SUCCESS : (state, action) => {
        state.isAuthenticated = true;
        state.loading = false;
        state.user = action.payload;
    },
    USER_LOADED_FAILURE : (state, action) => {
        state.isAuthenticated = false;
        state.loading = false;
        state.error = action.payload;
    },
    GOOGLE_SUCC:(state, action) => {
        state.isAuthenticated = true;
        state.loading = false;
        state.user = action.payload;
    },
    GOOGLE_FAIL : (state, action) => {
        state.isAuthenticated = false;
        state.loading = true;
        state.error = action.payload;
    },
    LOGOUT_SUCCESS: (state, action) => {
        state.isAuthenticated = false;
        state.loading = false;
        state.user = null;
    },
    LOGOUT_FAILURE: (state, action) => {
        state.isAuthenticated = true;
        state.loading = false;
        state.error = action.payload;
    },
    UPDATEPROFILE_SUCC : (state, action ) => {
        state.user = action.payload;
    },
    UPDATEPROFILE_FAIL : (state,action) => {
        state.error = action.payload;
    }

});

export const allUserReducer = createReducer(initialState, {
    ALLUSERS_SUCCESS: (state, action) => {
        state.loading = false;
        state.alluser = action.payload;
    },
    ALLUSERS_FAILURE: (state, action) => {
        state.loading = false;
        state.error = action.payload;
    }
} );

export const userProfileReducer = createReducer(initialState, {
    USERPROFILE_SUCCESS: (state, action) => {
        state.loading = false;
        state.userprofile = action.payload;
    },
    USERPROFILE_FAILURE: (state, action) => {
        state.loading = false;
        state.error = action.payload;
    },
} );
















































