import "./allstyle.css";
import { getAllUsers } from "../../actions/user";

import { useDispatch, useSelector } from "react-redux";
import { useEffect } from "react";

import AllUser from "./AllUser/AllUser";
const AllUsers =() => {

    const dispatch = useDispatch();
    useEffect(() => {
        dispatch(getAllUsers())
    } , [dispatch]);

    const {alluser} = useSelector((state) => state.allUsers);


    return(
        <div className="paralluser">
            <h2>TOP WRITERS </h2>
            <div>
                {(alluser && alluser.length > 0) && alluser.slice(0, 5).map(user => {
                    return(
                    <>
                        <AllUser user={user} key={user._id}  />
                    </>
                    )
                })}
            </div>            
        </div>
    )
};
export default AllUsers;