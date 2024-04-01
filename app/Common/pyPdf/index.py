import PyPDF2
import sys
import codecs
import json
import numpy

sys.stdout = codecs.getwriter('utf-8')(sys.stdout.detach())
fname = sys.argv[1]
# 打开PDF文件
pdf_file = open(fname, 'rb')

# 创建PDF文件阅读器对象
pdf_reader = PyPDF2.PdfReader(pdf_file)

# 获取PDF文档的页数
pages = pdf_reader.pages
ret = []
for page_index in range(len(pages)):
    page_content = pages[page_index].extract_text()
    ret.append({'ctx': page_content})

# 关闭PDF文件
pdf_file.close()
print(json.dumps(ret))