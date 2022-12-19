import './resetpassw.css';
import { useState } from "react";
import {TextField, Button} from "@material-ui/core";

import { useDispatch} from "react-redux";
import {  useNavigate, useParams } from "react-router-dom";
import { resetPassword } from "../../actions/user";




const ResetPassword = () => {
  const navigate = useNavigate();

  const [newPassword, setNewPassword] = useState("");
  const dispatch = useDispatch();

  const params = useParams();

  const submitHandler = (e) => {
    e.preventDefault();
    dispatch(resetPassword(params.token, newPassword, navigate));
  };

  return (
    <div className="reset-Password">
        <div className="reset-passw-main">
          <div className="reset-pass-window">
            <h1>Reset Password</h1>
            <form className="updatePasswordForm"onSubmit={submitHandler}>

            <TextField
                name="oldPassword"
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
                Reset Password
              </Button>
          </form>
          </div>
        </div>

      </div>
  );
};

export default ResetPassword;
