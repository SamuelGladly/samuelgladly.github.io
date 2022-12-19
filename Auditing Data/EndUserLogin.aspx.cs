using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;

public partial class EndUserLogin : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {

    }

    SqlConnection con = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con1 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con2 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con3 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd;
    SqlDataAdapter da;
    DataTable dt = new DataTable();
    SqlDataReader dr;
    SqlDataReader dr1;
    SqlDataReader dr2;
    DataSet ds = new DataSet();
    SqlConnection con4 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd4;


    protected void Button1_Click(object sender, EventArgs e)
    {
        con2.Open();
        cmd = new SqlCommand("select * from EndUserDetails where MailID='" + TextBox1.Text + "' and Password='" + TextBox2.Text + "'", con2);
        dr2 = cmd.ExecuteReader();
        if (dr2.Read())
        {
            Session["UserName"] = dr2[3].ToString();
            Session["UserMailID"] = TextBox1.Text;
            Response.Redirect("EndUserHome.aspx");
        }
        else
        {
            Response.Write("<script>alert('Invalid Data')</script>");
        }
        con2.Close();
    }
}