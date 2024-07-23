# coding:utf-8

from os import path
from PIL import Image
import numpy as np
import matplotlib.pyplot as plt
from wordcloud import WordCloud, STOPWORDS

def generate_wordcloud(text):

    # 设置显示方式
    d=path.dirname(__file__)
    cheers_mask = np.array(Image.open(path.join(d, "Images//724_mask.png")))
    font_path=path.join(d,"font//msyh.ttf")
    stopwords = set(STOPWORDS)
    wc = WordCloud(background_color="white",# 设置背景颜色
           max_words=700, # 词云显示的最大词数  
           mask=cheers_mask,# 设置背景图片       
           stopwords=stopwords, # 设置停用词
           font_path=font_path, # 兼容中文字体，不然中文会显示乱码
           collocations=False #避免重复词
                  )

    # 生成词云 
    wc.generate(text)

    # 生成的词云图像保存到本地
    wc.to_file(path.join(d, "Images//724.png"))

    # 显示图像
    # plt.imshow(wc, interpolation='bilinear')
    # # interpolation='bilinear' 表示插值方法为双线性插值
    # plt.axis("off")# 关掉图像的坐标
    # plt.show()

