import "./forgotpasswo.css"
import { useState } from "react";
import { useDispatch } from "react-redux";
import { Link } from "react-router-dom";
import {TextField, Button} from "@material-ui/core";
import { forgotPassword } from "../../actions/user";


const ForgotPassword = () => {
  const [email, setEmail] = useState("");

  const dispatch = useDispatch();
  
  const submitHandler = (e) => {
    e.preventDefault();
    dispatch(forgotPassword(email));
  };

  return (
    <div className="forgot-Password">
    <div className="forgot-passw-main">
      <div className="forgot-pass-window">
        <h1>Forgot Password</h1>
        <form className="updatePasswordForm"onSubmit={submitHandler}>

        <TextField
            name="email"
            variant="outlined"
            label="Email"
            fullWidth
            
            margin="normal"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
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
            Send Reset Link
          </Button>
      </form>
      </div>
    </div>

  </div>

  );
};

export default ForgotPassword;
