{
 "cells": [
  {
   "cell_type": "code",
   "execution_count": 22,
   "metadata": {},
   "outputs": [],
   "source": [
    "import numpy\n",
    "import os\n",
    "from keras import applications\n",
    "from keras.preprocessing.image import ImageDataGenerator\n",
    "from keras import optimizers\n",
    "from keras.models import Sequential, Model\n",
    "from keras.layers import Dropout, Flatten, Dense, GlobalAveragePooling2D\n",
    "from keras import backend as k\n",
    "from keras.callbacks import ModelCheckpoint, LearningRateScheduler, TensorBoard, EarlyStopping"
   ]
  },
  {
   "cell_type": "markdown",
   "metadata": {},
   "source": [
    "### 1. Load Test and Train Files"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": 23,
   "metadata": {},
   "outputs": [
    {
     "name": "stdout",
     "output_type": "stream",
     "text": [
      "(381, 164)\n"
     ]
    }
   ],
   "source": [
    "files_train = 0\n",
    "files_validation = 0\n",
    "\n",
    "cwd = os.getcwd()\n",
    "folder = 'train_data/train'\n",
    "for sub_folder in os.listdir(folder):\n",
    "    path, dirs, files = next(os.walk(os.path.join(folder,sub_folder)))\n",
    "    files_train += len(files)\n",
    "\n",
    "\n",
    "folder = 'train_data/test'\n",
    "for sub_folder in os.listdir(folder):\n",
    "    path, dirs, files = next(os.walk(os.path.join(folder,sub_folder)))\n",
    "    files_validation += len(files)\n",
    "\n",
    "print(files_train,files_validation)"
   ]
  },
  {
   "cell_type": "markdown",
   "metadata": {},
   "source": [
    "### 2. Set key parameters"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": 24,
   "metadata": {},
   "outputs": [],
   "source": [
    "img_width, img_height = 48, 48\n",
    "train_data_dir = \"train_data/train\"\n",
    "validation_data_dir = \"train_data/test\"\n",
    "nb_train_samples = files_train\n",
    "nb_validation_samples = files_validation\n",
    "batch_size = 32\n",
    "epochs = 15\n",
    "num_classes = 2"
   ]
  },
  {
   "cell_type": "markdown",
   "metadata": {},
   "source": [
    "### 3. Build model on top of a trained VGG"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": 25,
   "metadata": {},
   "outputs": [],
   "source": [
    "model = applications.VGG16(weights = \"imagenet\", include_top=False, input_shape = (img_width, img_height, 3))\n",
    "# Freeze the layers which you don't want to train. Here I am freezing the first 5 layers.\n",
    "for layer in model.layers[:10]:\n",
    "    layer.trainable = False"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": 26,
   "metadata": {},
   "outputs": [
    {
     "name": "stderr",
     "output_type": "stream",
     "text": [
      "/usr/local/lib/python2.7/dist-packages/ipykernel_launcher.py:10: UserWarning: Update your `Model` call to the Keras 2 API: `Model(outputs=Tensor(\"de..., inputs=Tensor(\"in...)`\n",
      "  # Remove the CWD from sys.path while we load stuff.\n"
     ]
    }
   ],
   "source": [
    "x = model.output\n",
    "x = Flatten()(x)\n",
    "# x = Dense(512, activation=\"relu\")(x)\n",
    "# x = Dropout(0.5)(x)\n",
    "# x = Dense(256, activation=\"relu\")(x)\n",
    "# x = Dropout(0.5)(x)\n",
    "predictions = Dense(num_classes, activation=\"softmax\")(x)\n",
    "\n",
    "# creating the final model\n",
    "model_final = Model(input = model.input, output = predictions)\n",
    "\n",
    "# compile the model\n",
    "model_final.compile(loss = \"categorical_crossentropy\", \n",
    "                    optimizer = optimizers.SGD(lr=0.0001, momentum=0.9), \n",
    "                    metrics=[\"accuracy\"]) # See learning rate is very low"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": 27,
   "metadata": {},
   "outputs": [
    {
     "name": "stdout",
     "output_type": "stream",
     "text": [
      "Found 381 images belonging to 2 classes.\n",
      "Found 164 images belonging to 2 classes.\n"
     ]
    }
   ],
   "source": [
    "# Initiate the train and test generators with data Augumentation\n",
    "train_datagen = ImageDataGenerator(\n",
    "rescale = 1./255,\n",
    "horizontal_flip = True,\n",
    "fill_mode = \"nearest\",\n",
    "zoom_range = 0.1,\n",
    "width_shift_range = 0.1,\n",
    "height_shift_range=0.1,\n",
    "rotation_range=5)\n",
    "\n",
    "test_datagen = ImageDataGenerator(\n",
    "rescale = 1./255,\n",
    "horizontal_flip = True,\n",
    "fill_mode = \"nearest\",\n",
    "zoom_range = 0.1,\n",
    "width_shift_range = 0.1,\n",
    "height_shift_range=0.1,\n",
    "rotation_range=5)\n",
    "\n",
    "train_generator = train_datagen.flow_from_directory(\n",
    "train_data_dir,\n",
    "target_size = (img_height, img_width),\n",
    "batch_size = batch_size,\n",
    "class_mode = \"categorical\")\n",
    "\n",
    "validation_generator = test_datagen.flow_from_directory(\n",
    "validation_data_dir,\n",
    "target_size = (img_height, img_width),\n",
    "class_mode = \"categorical\")"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": 28,
   "metadata": {},
   "outputs": [],
   "source": [
    "# Save the model according to the conditions\n",
    "checkpoint = ModelCheckpoint(\"car1.h5\", monitor='val_acc', verbose=1, save_best_only=True, save_weights_only=False, mode='auto', period=1)\n",
    "early = EarlyStopping(monitor='val_acc', min_delta=0, patience=10, verbose=1, mode='auto')\n"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": 29,
   "metadata": {},
   "outputs": [
    {
     "name": "stderr",
     "output_type": "stream",
     "text": [
      "/usr/local/lib/python2.7/dist-packages/ipykernel_launcher.py:9: UserWarning: The semantics of the Keras 2 argument `steps_per_epoch` is not the same as the Keras 1 argument `samples_per_epoch`. `steps_per_epoch` is the number of batches to draw from the generator at each epoch. Basically steps_per_epoch = samples_per_epoch/batch_size. Similarly `nb_val_samples`->`validation_steps` and `val_samples`->`steps` arguments have changed. Update your method calls accordingly.\n",
      "  if __name__ == '__main__':\n",
      "/usr/local/lib/python2.7/dist-packages/ipykernel_launcher.py:9: UserWarning: Update your `fit_generator` call to the Keras 2 API: `fit_generator(<keras.pre..., validation_data=<keras.pre..., steps_per_epoch=11, epochs=15, callbacks=[<keras.ca..., validation_steps=164)`\n",
      "  if __name__ == '__main__':\n"
     ]
    },
    {
     "name": "stdout",
     "output_type": "stream",
     "text": [
      "Epoch 1/15\n",
      "10/11 [==========================>...] - ETA: 0s - loss: 0.6248 - acc: 0.6210\n",
      "Epoch 00001: val_acc improved from -inf to 0.77449, saving model to car1.h5\n",
      "11/11 [==============================] - 6s 552ms/step - loss: 0.6193 - acc: 0.6245 - val_loss: 0.4398 - val_acc: 0.7745\n",
      "Epoch 2/15\n",
      " 8/11 [====================>.........] - ETA: 0s - loss: 0.4339 - acc: 0.7461\n",
      "Epoch 00002: val_acc improved from 0.77449 to 0.86131, saving model to car1.h5\n",
      "11/11 [==============================] - 5s 456ms/step - loss: 0.3886 - acc: 0.7774 - val_loss: 0.3228 - val_acc: 0.8613\n",
      "Epoch 3/15\n",
      " 9/11 [=======================>......] - ETA: 0s - loss: 0.2754 - acc: 0.9086\n",
      "Epoch 00003: val_acc improved from 0.86131 to 0.92204, saving model to car1.h5\n",
      "11/11 [==============================] - 5s 454ms/step - loss: 0.2646 - acc: 0.9196 - val_loss: 0.2556 - val_acc: 0.9220\n",
      "Epoch 4/15\n",
      "10/11 [==========================>...] - ETA: 0s - loss: 0.1922 - acc: 0.9515\n",
      "Epoch 00004: val_acc did not improve\n",
      "11/11 [==============================] - 5s 488ms/step - loss: 0.1850 - acc: 0.9560 - val_loss: 0.2326 - val_acc: 0.9041\n",
      "Epoch 5/15\n",
      " 8/11 [====================>.........] - ETA: 0s - loss: 0.1548 - acc: 0.9492\n",
      "Epoch 00005: val_acc did not improve\n",
      "11/11 [==============================] - 5s 428ms/step - loss: 0.1414 - acc: 0.9545 - val_loss: 0.2023 - val_acc: 0.9121\n",
      "Epoch 6/15\n",
      " 9/11 [=======================>......] - ETA: 0s - loss: 0.1199 - acc: 0.9722\n",
      "Epoch 00006: val_acc did not improve\n",
      "11/11 [==============================] - 5s 445ms/step - loss: 0.1205 - acc: 0.9716 - val_loss: 0.1817 - val_acc: 0.9182\n",
      "Epoch 7/15\n",
      "10/11 [==========================>...] - ETA: 0s - loss: 0.0952 - acc: 0.9650\n",
      "Epoch 00007: val_acc did not improve\n",
      "11/11 [==============================] - 6s 504ms/step - loss: 0.1014 - acc: 0.9625 - val_loss: 0.2241 - val_acc: 0.9121\n",
      "Epoch 8/15\n",
      "10/11 [==========================>...] - ETA: 0s - loss: 0.1144 - acc: 0.9594\n",
      "Epoch 00008: val_acc improved from 0.92204 to 0.92498, saving model to car1.h5\n",
      "11/11 [==============================] - 5s 474ms/step - loss: 0.1199 - acc: 0.9574 - val_loss: 0.1704 - val_acc: 0.9250\n",
      "Epoch 9/15\n",
      " 9/11 [=======================>......] - ETA: 0s - loss: 0.0650 - acc: 0.9792\n",
      "Epoch 00009: val_acc did not improve\n",
      "11/11 [==============================] - 5s 437ms/step - loss: 0.0641 - acc: 0.9772 - val_loss: 0.1691 - val_acc: 0.9200\n",
      "Epoch 10/15\n",
      "10/11 [==========================>...] - ETA: 0s - loss: 0.0774 - acc: 0.9812\n",
      "Epoch 00010: val_acc did not improve\n",
      "11/11 [==============================] - 5s 458ms/step - loss: 0.0723 - acc: 0.9830 - val_loss: 0.1960 - val_acc: 0.9194\n",
      "Epoch 11/15\n",
      "10/11 [==========================>...] - ETA: 0s - loss: 0.1063 - acc: 0.9647\n",
      "Epoch 00011: val_acc improved from 0.92498 to 0.93255, saving model to car1.h5\n",
      "11/11 [==============================] - 5s 436ms/step - loss: 0.1002 - acc: 0.9679 - val_loss: 0.1519 - val_acc: 0.9325\n",
      "Epoch 12/15\n",
      "10/11 [==========================>...] - ETA: 0s - loss: 0.0871 - acc: 0.9681\n",
      "Epoch 00012: val_acc did not improve\n",
      "11/11 [==============================] - 4s 363ms/step - loss: 0.0833 - acc: 0.9710 - val_loss: 0.1852 - val_acc: 0.9191\n",
      "Epoch 13/15\n",
      "10/11 [==========================>...] - ETA: 0s - loss: 0.0490 - acc: 0.9809\n",
      "Epoch 00013: val_acc did not improve\n",
      "11/11 [==============================] - 4s 399ms/step - loss: 0.0458 - acc: 0.9827 - val_loss: 0.1672 - val_acc: 0.9225\n",
      "Epoch 14/15\n",
      " 8/11 [====================>.........] - ETA: 0s - loss: 0.0839 - acc: 0.9766\n",
      "Epoch 00014: val_acc improved from 0.93255 to 0.93433, saving model to car1.h5\n",
      "11/11 [==============================] - 5s 436ms/step - loss: 0.0711 - acc: 0.9801 - val_loss: 0.1418 - val_acc: 0.9343\n",
      "Epoch 15/15\n",
      " 9/11 [=======================>......] - ETA: 0s - loss: 0.0435 - acc: 0.9896\n",
      "Epoch 00015: val_acc did not improve\n",
      "11/11 [==============================] - 4s 376ms/step - loss: 0.0451 - acc: 0.9886 - val_loss: 0.1616 - val_acc: 0.9279\n"
     ]
    }
   ],
   "source": [
    "### Start training!\n",
    "\n",
    "history_object = model_final.fit_generator(\n",
    "train_generator,\n",
    "samples_per_epoch = nb_train_samples,\n",
    "epochs = epochs,\n",
    "validation_data = validation_generator,\n",
    "nb_val_samples = nb_validation_samples,\n",
    "callbacks = [checkpoint, early])"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": 30,
   "metadata": {},
   "outputs": [
    {
     "name": "stdout",
     "output_type": "stream",
     "text": [
      "['acc', 'loss', 'val_acc', 'val_loss']\n"
     ]
    },
    {
     "data": {
      "image/png": "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\n",
      "text/plain": [
       "<Figure size 432x288 with 1 Axes>"
      ]
     },
     "metadata": {},
     "output_type": "display_data"
    }
   ],
   "source": [
    "import matplotlib.pyplot as plt\n",
    "print(history_object.history.keys())\n",
    "plt.plot(history_object.history['acc'])\n",
    "plt.plot(history_object.history['val_acc'])\n",
    "plt.title('model accuracy')\n",
    "plt.ylabel('acc')\n",
    "plt.xlabel('epoch')\n",
    "plt.legend(['train', 'test'], loc='upper left')\n",
    "plt.show()"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": 31,
   "metadata": {},
   "outputs": [
    {
     "data": {
      "image/png": "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\n",
      "text/plain": [
       "<Figure size 432x288 with 1 Axes>"
      ]
     },
     "metadata": {},
     "output_type": "display_data"
    }
   ],
   "source": [
    "plt.plot(history_object.history['loss'])\n",
    "plt.plot(history_object.history['val_loss'])\n",
    "plt.title('model loss')\n",
    "plt.ylabel('loss')\n",
    "plt.xlabel('epoch')\n",
    "plt.legend(['train', 'test'], loc='upper left')\n",
    "plt.show()"
   ]
  },
  {
   "cell_type": "code",
   "execution_count": null,
   "metadata": {},
   "outputs": [],
   "source": []
  }
 ],
 "metadata": {
  "kernelspec": {
   "display_name": "Python 2",
   "language": "python",
   "name": "python2"
  },
  "language_info": {
   "codemirror_mode": {
    "name": "ipython",
    "version": 2
   },
   "file_extension": ".py",
   "mimetype": "text/x-python",
   "name": "python",
   "nbconvert_exporter": "python",
   "pygments_lexer": "ipython2",
   "version": "2.7.12"
  }
 },
 "nbformat": 4,
 "nbformat_minor": 2
}