
import { configureStore } from '@reduxjs/toolkit';
import { myPostReducer, postReducer, tagReducer, userPostReducer,postDetailReducer, mySavedPostReducer, trendingPostReducer, searchPostReducer } from './reducers/post';
import { allUserReducer, userProfileReducer, userReducer } from './reducers/user';
// import { userReducer } from './Reducers/userR';

const store = configureStore({
    reducer: {
        user : userReducer,
        userProfile : userProfileReducer,
        allUsers : allUserReducer,
        posts : postReducer,
        myPosts : myPostReducer,
        tagPosts : tagReducer,
        
        post : postDetailReducer,
        userPosts : userPostReducer,
        mysavedPosts : mySavedPostReducer,
        trendPost :trendingPostReducer,
        searchPosts : searchPostReducer,
        
    }
});

export default store;













