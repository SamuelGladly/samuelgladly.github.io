using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

public partial class KeyGeneration : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {

    }

    protected void Button1_Click(object sender, EventArgs e)
    {
        if (TextBox1.Text == "kgc" && TextBox2.Text == "kgc")
        {
            Response.Redirect("KGCHome.aspx");
        }
        else
        {

            Response.Write("<script>alert('Invalid Data')</script>");
        }
    }
}