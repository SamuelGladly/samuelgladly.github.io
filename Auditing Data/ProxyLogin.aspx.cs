using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

public partial class ProxyLogin : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {

    }

    protected void Button1_Click(object sender, EventArgs e)
    {
        if (TextBox1.Text == "proxy" && TextBox2.Text == "proxy")
        {
            Response.Redirect("ProxyHome.aspx");
        }
        else
        {
           
            Response.Write("<script>alert('Invalid Data')</script>");
        }
    }
}