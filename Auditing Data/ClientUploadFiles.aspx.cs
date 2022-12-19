using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;
using System.Text;
using System.Security.Cryptography;
using System.IO.Compression;
using System.IO;

public partial class ClientUploadFiles : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {
        if (Session["ClientMailID"] != null && Session["ClientName"] != null)
        {
            TextBox1.Text = Session["ClientMailID"].ToString();
            TextBox2.Text = Session["ClientName"].ToString();
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

   
    private void EncryptFile()
    {
        
        //Build the File Path for the original (input) and the encrypted (output) file.
        string input = Server.MapPath("~/Files/") + TextBox3.Text + ".txt";
        string output = Server.MapPath("~/FilesE/") + TextBox3.Text + ".txt";

        //Save the Input File, Encrypt it and save the encrypted file in output path.
        FileUpload1.SaveAs(input);
        this.Encrypt(input, output);

    }

    private void Encrypt(string inputFilePath, string outputfilePath)
    {
        string EncryptionKey = "MAKV2SPBNI99212";
        using (Aes encryptor = Aes.Create())
        {
            Rfc2898DeriveBytes pdb = new Rfc2898DeriveBytes(EncryptionKey, new byte[] { 0x49, 0x76, 0x61, 0x6e, 0x20, 0x4d, 0x65, 0x64, 0x76, 0x65, 0x64, 0x65, 0x76 });
            encryptor.Key = pdb.GetBytes(32);
            encryptor.IV = pdb.GetBytes(16);
            using (FileStream fsOutput = new FileStream(outputfilePath, FileMode.Create))
            {
                using (CryptoStream cs = new CryptoStream(fsOutput, encryptor.CreateEncryptor(), CryptoStreamMode.Write))
                {
                    using (FileStream fsInput = new FileStream(inputFilePath, FileMode.Open))
                    {
                        int data;
                        while ((data = fsInput.ReadByte()) != -1)
                        {
                            cs.WriteByte((byte)data);
                        }
                    }
                }
            }
        }
    }

    private void fileupload()
    {
        con2.Open();
        cmd2 = new SqlCommand("select * from FileUpload where FileName='" + TextBox3.Text + "'", con2);
        dr2 = cmd2.ExecuteReader();
        if (dr2.Read())
        {
            Response.Write("<script>alert('File Name Already Exist')</script>");
        }
        else
        {
            string fileExtension = Path.GetExtension(FileUpload1.PostedFile.FileName);
            if (fileExtension == ".txt")
            {
                using (BinaryReader br = new BinaryReader(FileUpload1.PostedFile.InputStream))
                {

                    byte[] bytes = br.ReadBytes((int)FileUpload1.PostedFile.InputStream.Length);



                    int siz = (int)FileUpload1.PostedFile.InputStream.Length;
                    string Size = siz.ToString() + "kb";
                    // TextBox1.Text = size.ToString();

                    string contenttype = FileUpload1.PostedFile.ContentType;
                    // TextBox2.Text = FileUpload1.PostedFile.ContentType;

                    string filename = FileUpload1.FileName;
                    // TextBox3.Text = FileUpload1.FileName;

                    String filename1 = TextBox3.Text;


                    string date = DateTime.Now.ToString("dd/MM/yyyy");


                    using (SqlCommand cmd = new SqlCommand())
                    {
                        cmd.CommandText = "insert into FileUpload(ClientMailID, ClientName, FileName, UploadFile, ContentType, Data, FileSize, Date, ClientStatus, ProxyStatus, FileKey) values (@ClientMailID , @ClientName, @FileName, @UploadFile, @ContentType, @Data, @Size, @date, @ClientStatus, @ProxyStatus, @FileKey)";
                        cmd.Parameters.AddWithValue("@ClientMailID", TextBox1.Text);
                        cmd.Parameters.AddWithValue("@ClientName", TextBox2.Text);
                        cmd.Parameters.AddWithValue("@FileName", filename1);
                        cmd.Parameters.AddWithValue("@UploadFile", Path.GetFileName(FileUpload1.PostedFile.FileName));
                        cmd.Parameters.AddWithValue("@ContentType", "application/word");
                        cmd.Parameters.AddWithValue("@Data", bytes);
                        cmd.Parameters.AddWithValue("@Size", Size);
                        cmd.Parameters.AddWithValue("@Date", date);
                        cmd.Parameters.AddWithValue("@ClientStatus", "Waiting");
                        cmd.Parameters.AddWithValue("@ProxyStatus", "Waiting");
                        cmd.Parameters.AddWithValue("@FileKey", "Waiting");

                        cmd.Connection = con;
                        con.Open();
                        cmd.ExecuteNonQuery();
                        con.Close();

                        EncryptFile();


                        con4.Open();
                        cmd4 = new SqlCommand("insert into AuditDetails values('"+TextBox3.Text+"','File Upload','"+TextBox2.Text+"','"+DateTime.Now.ToString()+"')",con4);
                        cmd4.ExecuteNonQuery();
                        con4.Close();

                        Response.Write("<script>alert('File Upload Successfully')</script>");

                    }
                }
            }
            else
            {
                Response.Write("<script>alert('Text File Only Upload')</script>");
            }
        }
        con2.Close();

                    
        
    }

    private void ad()
    {

    }

    protected void Button1_Click(object sender, EventArgs e)
    {
        fileupload();
        TextBox3.Text = "";
    }
}