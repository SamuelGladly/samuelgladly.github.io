using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;
using System.IO;


public partial class EndUserSearchFile : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {
        if (Session["UserMailID"] != null && Session["UserName"] != null)
        {
            TextBox1.Text = Session["UserMailID"].ToString();
            TextBox2.Text = Session["UserName"].ToString();
        }
        if (!Page.IsPostBack)
        {
            dl();
        }
    }
    SqlConnection con = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con1 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con2 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con3 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd;
    SqlCommand cmd2;
    SqlDataAdapter da;
    DataTable dt = new DataTable();
    SqlDataReader dr;
    SqlDataReader dr1;
    SqlDataReader dr2;
    DataSet ds = new DataSet();
    SqlConnection con4 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd4;
    private void dl()
    {
        con.Open();
        cmd = new SqlCommand("select FileName from FileUpload where ClientStatus='Activated' and ProxyStatus='Activated'", con);
        da = new SqlDataAdapter(cmd);
        da.Fill(dt);
        DropDownList1.DataSource = dt;
        DropDownList1.DataBind();
        DropDownList1.DataTextField = "FileName";
        //  DropDownList1.DataValueField = "FileName";
        DropDownList1.DataBind();
        con.Close();
    }

    string cm = null;
    string cn = null;
    string fn = null;
    string uf = null;


    protected void Button1_Click(object sender, EventArgs e)
    {
        con.Open();
        cmd = new SqlCommand("select * from FileUpload where FileName='" + DropDownList1.Text + "' and ClientStatus='Activated' and ProxyStatus='Activated'", con);
        dr = cmd.ExecuteReader();
        if (dr.Read())
        {
            Panel1.Visible = true;

            con2.Open();
            cmd2 = new SqlCommand("select * from FileUpload where FileName='" + DropDownList1.Text + "'", con2);
            dr2 = cmd2.ExecuteReader();
            if (dr2.Read())
            {
                cm = dr2[1].ToString();
                cn = dr2[2].ToString();
                fn = dr2[3].ToString();
                uf = dr2[4].ToString();
                Label2.Text = cn;
                Label4.Text = fn;
                Label6.Text = uf;
                Label8.Text = "Active";
            }
            else
            {

            }
            con2.Close();
        }
        else
        {

        }
        con.Close();

        
    }

    protected void Button4_Click(object sender, EventArgs e)
    {
        Panel1.Visible = false;
    }

    protected void Button3_Click(object sender, EventArgs e)
    {
        con.Open();
        cmd = new SqlCommand("select * from FileRequest where FileName='" + Label4.Text + "' and UserMailID='"+TextBox1.Text+"' and FileKey='Waiting'", con);
        dr = cmd.ExecuteReader();
        if (dr.Read())
        {
            Response.Write("<script>alert('File Request Already Sent')</script>");
        }
        else
        {

            con3.Open();
            cmd = new SqlCommand("insert into FileRequest values('" + TextBox1.Text + "','" + TextBox2.Text + "','" + Label4.Text + "','Waiting','Get Request','" + DateTime.Now.ToString() + "')", con3);
            cmd.ExecuteNonQuery();
            con3.Close();


            Response.Write("<script>alert('Request Sent Successfully')</script>");
        }
        con.Close();

    }
}