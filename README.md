# API de Gerenciamento de Livros

Esta é uma API RESTful desenvolvida com Laravel 12 para gerenciamento de livros e seus índices. A aplicação é exclusivamente API-only, utilizando Laravel Sanctum para autenticação com tokens.

## Descrição

O sistema permite o gerenciamento completo de livros e seus índices, oferecendo funcionalidades como:

- Autenticação de usuários via token (Sanctum)
- CRUD completo de livros
- Importação de índices através de arquivos XML
- Processamento assíncrono de tarefas com filas (Jobs)
- Estrutura de índices recursivos/hierárquicos

## Tecnologias Utilizadas

- **PHP 8.4.8**
- **Laravel 12.21.0**
- **MySQL 9.3**
- **Laravel Sanctum** para autenticação API
- **Docker e Docker Compose** para containerização
- **Pest** para testes automatizados

## Requisitos

- Docker e Docker Compose
- Git

## Instalação

1. Clone o repositório:
   ```bash
   git clone [url-do-repositorio]
   cd books-crud
   ```

2. Configure o arquivo de ambiente:
   ```bash
   cp .env.example .env
   ```

3. Instale as dependências e gere a chave da aplicação:
   ```bash
   docker compose run --rm -it app composer install
   docker compose run --rm -it app php artisan key:generate
   ```

4. Inicie os containers Docker:
   ```bash
   docker compose up -d
   ```

5. Execute as migrações para criar as tabelas do banco de dados:
   ```bash
   docker compose exec app php artisan migrate --seed
   ```

## Testes

Execute os testes automatizados com Pest:

```bash
docker compose exec app php artisan test
```

Para ver detalhes e cobertura de código:

```bash
docker compose exec app php artisan test --coverage
```

## Documentação da API

Este projeto utiliza o [Bruno API](https://www.usebruno.com/) como cliente de API para testar e documentar os endpoints. A estrutura completa dos endpoints e a documentação da API podem ser encontradas na pasta `docs/` do projeto.

Para utilizar a documentação com o Bruno API:

1. Instale o Bruno API Client: https://www.usebruno.com/downloads
2. Abra o Bruno e selecione "Open Collection"
3. Navegue até a pasta `docs/` deste projeto
4. Todas as requisições estão organizadas por recursos e podem ser executadas diretamente do Bruno
