<?php
/**
 * Interface para geração de arquivos CNAB
 * Define o contrato que todas as implementações de CNAB devem seguir
 */
interface CnabInterface {
    /**
     * Gera o arquivo de remessa CNAB
     * 
     * @param array $dadosBanco Dados do banco (agência, conta, etc.)
     * @param array $titulos Array de títulos a serem incluídos no arquivo
     * @param string $caminhoDestino Caminho onde o arquivo será salvo
     * @return string Caminho completo do arquivo gerado
     * @throws Exception Se houver erro na geração
     */
    public function gerarRemessa(array $dadosBanco, array $titulos, string $caminhoDestino): string;
    
    /**
     * Retorna o código do banco (ex: 001 para Banco do Brasil)
     * 
     * @return string
     */
    public function getCodigoBanco(): string;
    
    /**
     * Retorna o nome do banco
     * 
     * @return string
     */
    public function getNomeBanco(): string;
    
    /**
     * Retorna a versão do CNAB suportada (240 ou 400)
     * 
     * @return int
     */
    public function getVersaoCnab(): int;
    
    /**
     * Valida os dados do banco antes de gerar o arquivo
     * 
     * @param array $dadosBanco
     * @return bool
     * @throws Exception Se os dados forem inválidos
     */
    public function validarDadosBanco(array $dadosBanco): bool;
    
    /**
     * Valida os dados de um título
     * 
     * @param array $titulo
     * @return bool
     * @throws Exception Se os dados forem inválidos
     */
    public function validarTitulo(array $titulo): bool;
}





