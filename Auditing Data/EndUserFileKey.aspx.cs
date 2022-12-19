using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

public partial class EndUserFileKey : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {
        if (Session["UserMailID"] != null && Session["UserName"] != null)
        {
            TextBox1.Text = Session["UserMailID"].ToString();
            TextBox2.Text = Session["UserName"].ToString();
        }
    }
}