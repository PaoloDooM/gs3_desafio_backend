## Desafio GS3 Tecnologia (Full stack)

1.  Criar um sistema que gerencie usuários e perfis.
2.  Usuário possui um perfil; um perfil pode ter vários usuários.
3.  O sistema deverá ter um administrador que crie os usuários e atribua ou modifique os perfis.
4.  O perfil usuário comum apenas visualizará suas próprias informações, podendo editá-las,  
    menos o perfil.
5.  Favor não utilizar os plugins do laravel que já trazem pronto esta solução, tipo o spatie/laravel-  
    permission.
6.  Utilizar no frontend a versão mais recente do Flutter.
7.  Utilizar no back o banco de sua preferência, preferencialmente PHP > 8 + Laravel 11.
8.  Será avaliado o código e o sistema rodando, favor encaminhar o link funcional ou as  
    instruções para subir a aplicação.
9.  Prazo para fazer o desafio: 1 semana.

## Instruções para executar o projeto backend

Para executar o projeto, deverá ser utilizado Docker. Este projeto é composto por dois contêineres, um para o banco de dados PostgreSQL e outro para a aplicação Laravel. O seguinte comando construirá as imagens, levantará os contêineres e rodará as migrações.

```plaintext
docker-compose up --build -d
```
