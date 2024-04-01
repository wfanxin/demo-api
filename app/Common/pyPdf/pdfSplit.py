import PyPDF2
import sys

pdf_path = sys.argv[1]
out_path = sys.argv[2]
start_page = int(sys.argv[3])
page = int(sys.argv[4])

# 打开PDF文件
# pdf_file = open('/www/wwwroot/linw/dh_erp_ph/public/tmp/delivery/2024022309241849898.pdf', 'rb')  # 1 页的
pdf_file = open(pdf_path, 'rb')

pdf_reader = PyPDF2.PdfReader(pdf_file)

# 拆分前四页
# pdf_writer = PyPDF2.PdfWriter()
# for page_num in range(1):  #选择页数
#     pdf_writer.add_page(pdf_reader.pages[page_num])
# output_filename = '/www/wwwroot/linw/dh_erp_ph/public/tmp/delivery/pages_1_to_4.pdf'
# with open(output_filename, 'wb') as output:
#     pdf_writer.write(output)

# 拆分5-7页
pdf_writer = PyPDF2.PdfWriter()
for page_num in range(start_page - 1, start_page - 1 + page):  #选择页数
    pdf_writer.add_page(pdf_reader.pages[page_num])
output_filename = out_path
with open(output_filename, 'wb') as output:
    pdf_writer.write(output)

# 关闭PDF文件
pdf_file.close()
