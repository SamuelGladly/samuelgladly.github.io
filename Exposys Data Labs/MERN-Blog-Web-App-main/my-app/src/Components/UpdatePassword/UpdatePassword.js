import "./updatpass.css"
import { useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useNavigate } from "react-router-dom";
import { updatePassword } from "../../actions/user";
import {TextField, MenuItem, Button} from "@material-ui/core";


const UpdatePassword = () => {
    const [oldPassword, setOldPassword] = useState("");
    const [newPassword, setNewPassword] = useState("");
    
  
    const dispatch = useDispatch();
    const navigate = useNavigate();
    // const { error, loading, message } = useSelector((state) => state.like);
    
    console.log(oldPassword,newPassword);
    const submitHandler = (e) => {
      e.preventDefault();
      dispatch(updatePassword(oldPassword, newPassword, navigate));
    };

  
  
    return (
      <div className="updatePassword">
        <div className="update-passw-main">
          <div className="update-pass-window">
            <h1>Update Password</h1>
            <form className="updatePasswordForm" onSubmit={submitHandler}>

            <TextField
                name="oldPassword"
                variant="outlined"
                label="Old Password"
                fullWidth
                type="password"
                margin="normal"
                value={oldPassword}
                onChange={(e) => setOldPassword(e.target.value)}
                InputProps={{ style: { fontSize: 17 } }}
                InputLabelProps={{ style: { fontSize: 17 } }}
            />

            <TextField
                name="newPassword"
                variant="outlined"
                label="New Password"
                fullWidth
                type="password"
                margin="normal"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                InputProps={{ style: { fontSize: 17 } }}
                InputLabelProps={{ style: { fontSize: 17 } }}
            />
              <Button
                variant="contained"
                color="primary"
                size="large"
                margin='normal'
                style={{ lineHeight: "30px", fontSize:'20px', backgroundColor:'#f6a573',textTransform:'capitalize',margin:'10px 0px', color:'black'  }}
                type="submit"
                fullWidth
              >
                Update Password
              </Button>
          </form>
          </div>
        </div>

      </div>
    );
  };
  
  export default UpdatePassword;
  