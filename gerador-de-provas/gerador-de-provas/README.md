# 📝 Gerador de Provas

Sistema para professores criarem provas online, aplicarem para os alunos e corrigirem automaticamente. Criado para uso real em sala de aula, nas turmas de ensino técnico em que leciono.

O professor digita as questões num formato de texto simples, o sistema gera uma prova interativa em HTML, os alunos respondem e as respostas são corrigidas e salvas num banco MySQL, com nota e média da turma.

## Funcionalidades

**Front-end (funciona sem servidor)**
- `gerador-questoes.html`: monta a prova a partir de texto, com prévia ao vivo, peso por questão, dados da turma e tempo de prova. Gera o arquivo da prova para o aluno e o `gabarito.json`
- `corretor.html`: corrige respostas na hora, sem precisar de banco de dados
- `lista-de-fixacao.html`: versão para montar listas de exercícios de fixação

**Back-end (PHP + MySQL)**
- `importar_gabarito.php`: grava a prova e as questões no banco
- `importar_resposta.php`: recebe um ou vários arquivos de resposta, corrige e grava aluno, respostas e nota
- `listar_resultados.php`: tabela de resultados com filtro por prova e média da turma

## Tecnologias

HTML · CSS · JavaScript · PHP (PDO) · MySQL

## Fluxo

```
gerador-questoes.html  →  prova.html + gabarito.json
                                │
                    importar_gabarito.php  →  grava provas + questões
                                │
prova.html (aluno responde)  →  resposta-nome.json
                                │
                    importar_resposta.php  →  corrige e grava alunos + respostas + resultados
                                │
                    listar_resultados.php  →  visão da turma
```

## Formato das questões

```
Qual ferramenta do Photoshop seleciona áreas de cor parecida?
A) Pincel
*B) Varinha mágica
C) Borracha
D) Laço
peso: 1
```

A alternativa correta é marcada com `*`.

## Como rodar o back-end (XAMPP)

1. Copie a pasta `backend` para `C:\xampp\htdocs\sistema-provas\`
2. Ligue o Apache e o MySQL no painel do XAMPP
3. Abra `http://localhost/phpmyadmin`, vá na aba **SQL** e execute o `schema.sql`
4. Se o seu MySQL não usa o padrão (usuário `root` sem senha), ajuste o `config.php`
5. Acesse `http://localhost/sistema-provas/importar_gabarito.php`

## Banco de dados

Cinco tabelas: `alunos`, `provas`, `questoes`, `respostas` e `resultados`, com chaves estrangeiras e restrições de unicidade. Reimportar o mesmo gabarito ou a mesma resposta **atualiza** os dados em vez de duplicar. Todas as consultas usam *prepared statements*.

## Próximos passos

- Dashboard com gráficos de desempenho por questão e por turma
- Login para o professor

## Autor

**José Augusto** · [LinkedIn](https://www.linkedin.com/in/josearraes)
