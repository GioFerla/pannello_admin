-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Creato il: Feb 06, 2026 alle 07:25
-- Versione del server: 8.0.45
-- Versione PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eventi_db`
--
CREATE DATABASE IF NOT EXISTS `eventi_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `eventi_db`;

-- --------------------------------------------------------

--
-- Struttura della tabella `AMBITO`
--

CREATE TABLE `AMBITO` (
  `idAmbito` int NOT NULL,
  `nome` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `CONTATTO`
--

CREATE TABLE `CONTATTO` (
  `idContatto` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `EVENTO`
--

CREATE TABLE `EVENTO` (
  `idEvento` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descrizione` text,
  `dataOraInizio` datetime NOT NULL,
  `dataOraFine` datetime DEFAULT NULL,
  `dataOraPubblicazione` datetime NOT NULL,
  `idAmbito` int NOT NULL,
  `idSede` int NOT NULL,
  `idContatto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `EVENTO_MULTIMEDIA`
--

CREATE TABLE `EVENTO_MULTIMEDIA` (
  `idEvento` int NOT NULL,
  `idMultimedia` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `MULTIMEDIA`
--

CREATE TABLE `MULTIMEDIA` (
  `idMultimedia` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipoFile` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `SEDE`
--

CREATE TABLE `SEDE` (
  `idSede` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `via` varchar(100) NOT NULL,
  `citta` varchar(50) NOT NULL,
  `provincia` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `AMBITO`
--
ALTER TABLE `AMBITO`
  ADD PRIMARY KEY (`idAmbito`);

--
-- Indici per le tabelle `CONTATTO`
--
ALTER TABLE `CONTATTO`
  ADD PRIMARY KEY (`idContatto`);

--
-- Indici per le tabelle `EVENTO`
--
ALTER TABLE `EVENTO`
  ADD PRIMARY KEY (`idEvento`),
  ADD KEY `idAmbito` (`idAmbito`),
  ADD KEY `idSede` (`idSede`),
  ADD KEY `idContatto` (`idContatto`);

--
-- Indici per le tabelle `EVENTO_MULTIMEDIA`
--
ALTER TABLE `EVENTO_MULTIMEDIA`
  ADD PRIMARY KEY (`idEvento`,`idMultimedia`),
  ADD KEY `idMultimedia` (`idMultimedia`);

--
-- Indici per le tabelle `MULTIMEDIA`
--
ALTER TABLE `MULTIMEDIA`
  ADD PRIMARY KEY (`idMultimedia`);

--
-- Indici per le tabelle `SEDE`
--
ALTER TABLE `SEDE`
  ADD PRIMARY KEY (`idSede`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `AMBITO`
--
ALTER TABLE `AMBITO`
  MODIFY `idAmbito` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `CONTATTO`
--
ALTER TABLE `CONTATTO`
  MODIFY `idContatto` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `EVENTO`
--
ALTER TABLE `EVENTO`
  MODIFY `idEvento` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `MULTIMEDIA`
--
ALTER TABLE `MULTIMEDIA`
  MODIFY `idMultimedia` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `SEDE`
--
ALTER TABLE `SEDE`
  MODIFY `idSede` int NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `EVENTO`
--
ALTER TABLE `EVENTO`
  ADD CONSTRAINT `EVENTO_ibfk_1` FOREIGN KEY (`idAmbito`) REFERENCES `AMBITO` (`idAmbito`),
  ADD CONSTRAINT `EVENTO_ibfk_2` FOREIGN KEY (`idSede`) REFERENCES `SEDE` (`idSede`),
  ADD CONSTRAINT `EVENTO_ibfk_3` FOREIGN KEY (`idContatto`) REFERENCES `CONTATTO` (`idContatto`);

--
-- Limiti per la tabella `EVENTO_MULTIMEDIA`
--
ALTER TABLE `EVENTO_MULTIMEDIA`
  ADD CONSTRAINT `EVENTO_MULTIMEDIA_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `EVENTO` (`idEvento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `EVENTO_MULTIMEDIA_ibfk_2` FOREIGN KEY (`idMultimedia`) REFERENCES `MULTIMEDIA` (`idMultimedia`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
