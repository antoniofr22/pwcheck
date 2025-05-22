# 🔐 Verificador de Senhas Vazadas (API PHP)

API em PHP para verificar se uma senha foi exposta em vazamentos de dados utilizando a API do Have I Been Pwned.

## ✅ Funcionalidades

- Verificação de senhas com a API `Pwned Passwords`
- Armazenamento em cache por 24 horas
- Logs diários com senhas mascaradas
- Suporte a requisições POST com JSON

## 🧰 Tecnologias Utilizadas

- PHP (sem frameworks)
- API Have I Been Pwned (k-Anonymity)
- Armazenamento local (cache e logs)
- JSON para entrada e saída de dados

## 📦 Estrutura

- `index.php`: ponto de entrada da API
- `cache/`: armazena resultados por 24h
- `logs/`: armazena logs de consultas

## ⚙️ Requisitos
- PHP 7.0 ou superior
- Permissões de escrita em disco
