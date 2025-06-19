<?php

namespace App\Form;

use App\Entity\News;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;

class NewsForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Haber Başlığı',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Haber başlığını giriniz'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Haber başlığı boş olamaz']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Haber başlığı en fazla {{ limit }} karakter olabilir'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Haber Açıklaması',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Haber açıklamasını giriniz',
                    'rows' => 3
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Haber açıklaması en fazla {{ limit }} karakter olabilir'
                    ])
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Kategori',
                'choices' => [
                    'Habbo' => 'Habbo',
                    'Habbocan' => 'Habbocan',
                    'Yol Haritaları' => 'Yol Haritaları',
                    'Etkinlikler' => 'Etkinlikler',
                    'Habbo Mağaza' => 'Habbo Mağaza'
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => true
            ])
            ->add('coverImage', FileType::class, [
                'label' => 'Kapak Fotoğrafı',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Lütfen geçerli bir resim dosyası yükleyin (JPEG, PNG, GIF, WEBP)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('badgeAvailability', ChoiceType::class, [
                'label' => 'Rozet Kullanılabilirliği',
                'choices' => [
                    'Kullanılabilir' => 'available',
                    'Kullanılamaz' => 'unavailable'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('badgeCodes', TextType::class, [
                'label' => 'Rozet Kodları',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Rozet kodlarını giriniz (örn: TR001,TR002)'
                ],
                'required' => false
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Durum',
                'choices' => [
                    'Taslak' => 'draft',
                    'Yayında' => 'published',
                    'Arşivlenmiş' => 'archived'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('commentsEnabled', CheckboxType::class, [
                'label' => 'Yorumlar Açık',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('isPinned', CheckboxType::class, [
                'label' => 'Sabitlenmiş Haber',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Haber İçeriği',
                'attr' => [
                    'class' => 'form-control tinymce',
                    'placeholder' => 'Haber içeriğini giriniz',
                    'rows' => 15
                ],
                'required' => false
            ])
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => News::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'news_form',
        ]);
    }
}
