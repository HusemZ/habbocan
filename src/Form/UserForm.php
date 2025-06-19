<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Kullanıcı Adı',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Kullanıcı adını giriniz'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Kullanıcı adı boş olamaz']),
                    new Length([
                        'min' => 3,
                        'max' => 180,
                        'minMessage' => 'Kullanıcı adı en az {{ limit }} karakter olmalıdır',
                        'maxMessage' => 'Kullanıcı adı en fazla {{ limit }} karakter olabilir'
                    ])
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Roller',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Kullanıcı' => 'ROLE_USER',
                    'Oyun İçi Destek' => 'ROLE_IN_GAME_HELPER',
                    'Baş Oyun İçi Destek' => 'ROLE_HEAD_IN_GAME_HELPER',
                    'Grafiker' => 'ROLE_GRAPHICER',
                    'Mobinator' => 'ROLE_MOBINATOR',
                    'Haberci' => 'ROLE_EDITOR',
                    'Mimar' => 'ROLE_ARCHITECT',
                    'Baş Haberci' => 'ROLE_HEAD_EDITOR',
                    'Baş Mimar' => 'ROLE_HEAD_ARCHITECT',
                    'Moderatör' => 'ROLE_MODERATOR',
                    'Yönetici' => 'ROLE_ADMIN',
                    'Developer' => 'ROLE_DEVELOPER',
                ],
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'choice_attr' => function ($choice, $key, $value) {
                    return ['class' => 'form-check-input'];
                }
            ])
            ->add('plainPanelPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Yeni Panel Şifresi (Opsiyonel)',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Yeni panel şifresini giriniz (boş bırakabilirsiniz)'
                    ]
                ],
                'second_options' => [
                    'label' => 'Yeni Panel Şifresi Tekrar',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Yeni panel şifresini tekrar giriniz'
                    ]
                ],
                'invalid_message' => 'Panel şifreleri eşleşmiyor',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Panel şifresi en az {{ limit }} karakter olmalıdır',
                        'max' => 4096,
                    ])
                ]
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_panel_password' => false,
            'include_email' => false,
            'include_is_active' => false,
        ]);
    }
}
