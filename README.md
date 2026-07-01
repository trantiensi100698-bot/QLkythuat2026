# Dathop - Web Quan Ly Ky Thuat

Web noi bo quan ly ky thuat cho Dathop, gom 4 phan chinh:

1. **Du an R&D**: quan ly thi nghiem (vi du xu ly khi doc NH4/NO2, xu ly nuoc truoc tha giong), ghi nhan chi tieu do dac truoc/sau khi dung san pham, san pham su dung + chi phi, danh gia uu/nhuoc diem, phan tich chi phi, dinh kem file bao cao Word/PPT/Excel/PDF de chia se.
2. **Nhat ky farm nuoi Biogency**: quan ly danh sach ao cua farm, ghi nhat ky theo ngay/theo ao (chi tieu moi truong, luong cho an, san pham da dung, hinh anh, ghi chu bat thuong).
3. **Quan ly ky thuat thi truong**
   - **Ho tro ky thuat**:
     - Thu vien cong thuc ket hop san pham & quy trinh xu ly (khi doc, gan, duong ruot, mau nuoc, uong gieo, ao lang, day ao/nhot bat...)
     - Chan doan ao khach hang: sale nhap dien tich ao + chi tieu moi truong + hinh anh, RD tu van tu xa dua tren du lieu, gan quy trinh phu hop tu thu vien
   - **Ho tro thi truong**: ghi nhan thuyet trinh/demo san pham, tham ao dinh ky, chuyen giao cong nghe - kem mau kiem tra tai ao, hinh anh, phan hoi khach hang va file bao cao chuyen di (Word/PPT/Excel/PDF) de gui Ms Tu Anh.
4. Dang nhap phan quyen: `rd` (R&D ky thuat), `sale` (nhan vien thi truong), `manager` (quan ly)

Stack: PHP thuan (khong framework) + MySQL + Bootstrap 5 (CDN). Chon PHP/MySQL de chay duoc tren moi goi hosting cua Hostinger, ke ca shared hosting re nhat (khong can Node.js/VPS).

## Cai dat local (Windows, dung XAMPP hoac PHP built-in server)

1. Cai PHP 8.x va MySQL (hoac dung XAMPP/Laragon co san ca hai).
2. Tao database va import schema:
   ```
   mysql -u root -p -e "CREATE DATABASE dathop_ky_thuat CHARACTER SET utf8mb4"
   mysql -u root -p dathop_ky_thuat < sql/schema.sql
   ```
3. Copy `config.example.php` thanh `config.php`, dien lai thong tin database va `base_url` (vi du `http://localhost:8000`).
4. Tao tai khoan dau tien (role R&D) qua dong lenh:
   ```
   php includes/create_admin.php "Nguyen Van A" rd@dathop.com.vn "MatKhauManh123"
   ```
5. Chay server thu:
   ```
   php -S localhost:8000
   ```
6. Mo trinh duyet: `http://localhost:8000/login.php`

Sau khi dang nhap bang tai khoan RD, vao **Danh muc > San pham Biogency** de nhap danh sach san pham, roi vao **Thu vien cong thuc / quy trinh** de tao cac quy trinh chuan (khi doc, gan, duong ruot, mau nuoc, uong gieo, ao lang, day ao/nhot bat).

De tao them tai khoan cho Hieu (sale) hoac Ms Tu Anh (manager), chay lai `create_admin.php` roi vao MySQL sua truc tiep cot `role` thanh `sale` hoac `manager` (hoac yeu cau bo sung trang quan ly user rieng neu can).

## Deploy len Hostinger

Ap dung duoc voi **moi goi Hostinger** (Shared/Business/Premium) vi chi can PHP + MySQL:

1. Trong hPanel Hostinger: tao 1 database MySQL + 1 user co full quyen tren database do (muc **Databases > MySQL Databases**). Ghi lai host (thuong la `localhost`), ten database, user, mat khau.
2. Vao **File Manager** (hoac dung FTP/SFTP), tro domain/subdomain toi thu muc goc cua project nay (thuong la `public_html` hoac 1 subfolder trong do). Upload toan bo code (tru `config.php` va thu muc `uploads/` du lieu that).
3. Trong **Databases > phpMyAdmin**, import file `sql/schema.sql` vao database vua tao.
4. Tren hosting, tao file `config.php` (copy tu `config.example.php`) voi thong tin database that va `base_url` la domain that, vi du `https://kythuat.dathop.com.vn`.
5. Neu Hostinger co ho tro SSH: chay `php includes/create_admin.php ...` de tao tai khoan dau tien. Neu khong co SSH, tam thoi insert truc tiep qua phpMyAdmin (dung ham `PASSWORD_DEFAULT` cua PHP de tao hash - co the nho AI/dev tao gium 1 chuoi hash roi INSERT thu cong).
6. Kiem tra thu muc `uploads/` co quyen ghi (thuong 755 hoac 775 tuy hosting).
7. Truy cap domain, dang nhap va bat dau su dung.

**Bao mat**: file `.htaccess` da chan truy cap truc tiep vao `config.php`, thu muc `includes/` va `sql/`. Neu hosting dung Nginx thay vi Apache, can cau hinh chan tuong duong (hoi bo phan ho tro ky thuat cua Hostinger neu can).

## Cau truc thu muc

```
/index.php, login.php, logout.php     Trang chinh + xac thuc
/includes/                             Ket noi DB, phan quyen, layout dung chung
/modules/rnd/                          Du an R&D: thi nghiem, so lieu, chi phi, bao cao
/modules/nhat-ky-farm/                 Nhat ky farm Biogency: quan ly ao + nhat ky theo ngay
/modules/ho-tro-ky-thuat/thu-vien/     Thu vien cong thuc & quy trinh xu ly
/modules/ho-tro-ky-thuat/chan-doan/    Chan doan ao khach hang
/modules/ho-tro-thi-truong/            Thuyet trinh/demo, tham ao dinh ky, chuyen giao cong nghe
/sql/schema.sql                        Cau truc database
/uploads/                              Anh/file upload (khong commit len git)
```

## Chua lam / huong phat trien tiep

- Trang quan ly nguoi dung rieng (hien tai tao tai khoan qua CLI `create_admin.php`, doi role phai sua truc tiep trong database).
- Export/tong hop du lieu R&D va bao cao chuyen di thanh file Word/PPT/Excel tu dong (hien tai la upload file da lam san, chua tu sinh file).
- Thong bao (email/Zalo...) khi co ca chan doan moi hoac tu van moi.
