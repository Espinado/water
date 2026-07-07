<?php

return [

    'required' => 'Поле «:attribute» обязательно для заполнения.',
    'numeric' => 'Поле «:attribute» должно быть числом.',
    'email' => 'Поле «:attribute» должно быть действительным email.',
    'min' => [
        'numeric' => 'Поле «:attribute» должно быть не меньше :min.',
    ],
    'max' => [
        'numeric' => 'Поле «:attribute» не может быть больше :max.',
        'file' => 'Файл «:attribute» не может быть больше :max КБ.',
    ],
    'confirmed' => 'Подтверждение поля «:attribute» не совпадает.',
    'unique' => 'Такое значение поля «:attribute» уже используется.',
    'image' => 'Поле «:attribute» должно быть изображением.',
    'current_password' => 'Текущий пароль указан неверно.',

];
