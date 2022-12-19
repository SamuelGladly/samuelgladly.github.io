using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

public partial class CloudServer : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {

    }

    protected void Button1_Click(object sender, EventArgs e)
    {
        if (TextBox1.Text == "server" && TextBox2.Text == "server")
        {
            Response.Redirect("CloudServerHome.aspx");
        }
        else
        {
            Response.Write("<script>alert('Invalid Data')</script>");
        }
    }
}