-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2026 at 04:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `activity_log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `date_log` datetime NOT NULL DEFAULT current_timestamp(),
  `action` longtext NOT NULL DEFAULT '',
  `session_id` varchar(255) NOT NULL DEFAULT '',
  `user_level` varchar(100) NOT NULL DEFAULT '0',
  `system_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`activity_log_id`, `user_id`, `date_log`, `action`, `session_id`, `user_level`, `system_id`) VALUES
(2, 1, '2026-03-04 09:45:25', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":1,\"general_id\":\"CGC-08626\",\"full_name\":\"REOLO, MARLON LLANES\",\"old_employment_status_id\":1,\"new_employment_status_id\":1}', 'Z25vRzBEbzhDM3NRRnpmZStEeE1WVGpUOERFaG9SN0paTjVBSFh0c2hNdTNRNmFMc2dkY2lDQlkxT05LeUY4dU00UTN1U3NWaDZ5ZDNJY3ZDSTVjTnVieXorTXBaUmxxZjNqcXRCUTFHemUyK0E3ZXdLZGV2eVJueGc1SndEcmpuUzByelR6ZlVxQkM4ZldQaWhLQ3NlUm0xUHp1N0haTVhsS1YwT011VEFjZ3JoRlNIeGloYWU4d25qMHRMRnh', '0', 0),
(3, 1, '2026-03-04 09:50:39', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":1,\"general_id\":\"CGC-08626\",\"full_name\":\"REOLO, MARLON LLANES\",\"old_employment_status_id\":1,\"new_employment_status_id\":1}', 'Z25vRzBEbzhDM3NRRnpmZStEeE1WVGpUOERFaG9SN0paTjVBSFh0c2hNdTNRNmFMc2dkY2lDQlkxT05LeUY4dU00UTN1U3NWaDZ5ZDNJY3ZDSTVjTnVieXorTXBaUmxxZjNqcXRCUTFHemUyK0E3ZXdLZGV2eVJueGc1SndEcmpuUzByelR6ZlVxQkM4ZldQaWhLQ3NlUm0xUHp1N0haTVhsS1YwT011VEFjZ3JoRlNIeGloYWU4d25qMHRMRnh', '0', 0),
(4, 1, '2026-03-04 09:50:56', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":1,\"general_id\":\"CGC-08626\",\"full_name\":\"REOLO, MARLON LLANES\",\"old_employment_status_id\":1,\"new_employment_status_id\":2}', 'Z25vRzBEbzhDM3NRRnpmZStEeE1WVGpUOERFaG9SN0paTjVBSFh0c2hNdTNRNmFMc2dkY2lDQlkxT05LeUY4dU00UTN1U3NWaDZ5ZDNJY3ZDSTVjTnVieXorTXBaUmxxZjNqcXRCUTFHemUyK0E3ZXdLZGV2eVJueGc1SndEcmpuUzByelR6ZlVxQkM4ZldQaWhLQ3NlUm0xUHp1N0haTVhsS1YwT011VEFjZ3JoRlNIeGloYWU4d25qMHRMRnh', '0', 0),
(5, 1, '2026-03-04 09:51:02', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":1,\"general_id\":\"CGC-08626\",\"full_name\":\"REOLO, MARLON LLANES\",\"old_employment_status_id\":2,\"new_employment_status_id\":1}', 'Z25vRzBEbzhDM3NRRnpmZStEeE1WVGpUOERFaG9SN0paTjVBSFh0c2hNdTNRNmFMc2dkY2lDQlkxT05LeUY4dU00UTN1U3NWaDZ5ZDNJY3ZDSTVjTnVieXorTXBaUmxxZjNqcXRCUTFHemUyK0E3ZXdLZGV2eVJueGc1SndEcmpuUzByelR6ZlVxQkM4ZldQaWhLQ3NlUm0xUHp1N0haTVhsS1YwT011VEFjZ3JoRlNIeGloYWU4d25qMHRMRnh', '0', 0),
(6, 1, '2026-03-04 10:09:50', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":1,\"general_id\":\"CGC-08626\",\"full_name\":\"REOLO, MARLON LLANES\",\"old_employment_status_id\":1,\"new_employment_status_id\":3}', 'd3B2dDloWW1RNFQwdUowTSttMkVnK1BWQTVXQjljcHFNekJZTG5Wd3VBMUxqVzYrcEt4Y1Y2M0VadjZSMitPRDBKblJwR1ZkVDZ2bHpkVGZ0b1E4cDBrS3JBOHJXbytqc29qV3NXTWxZOFVXWGpDaEdLUGlLZDVJYW5sSW5VaytrSFpVMm04QlRseE4zNWdZSmwxUjZDcW94Tm9ZMmlKeVRVUTJxSUxwSmo2U0dKZFF5bTlVbVVHbjlTeVBpVnE', '1', 0),
(7, 1, '2026-03-04 10:10:02', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":1,\"general_id\":\"CGC-08626\",\"full_name\":\"REOLO, MARLON LLANES\",\"old_employment_status_id\":3,\"new_employment_status_id\":1}', 'd3B2dDloWW1RNFQwdUowTSttMkVnK1BWQTVXQjljcHFNekJZTG5Wd3VBMUxqVzYrcEt4Y1Y2M0VadjZSMitPRDBKblJwR1ZkVDZ2bHpkVGZ0b1E4cDBrS3JBOHJXbytqc29qV3NXTWxZOFVXWGpDaEdLUGlLZDVJYW5sSW5VaytrSFpVMm04QlRseE4zNWdZSmwxUjZDcW94Tm9ZMmlKeVRVUTJxSUxwSmo2U0dKZFF5bTlVbVVHbjlTeVBpVnE', '1', 0),
(8, 1, '2026-03-04 10:13:46', 'AST CATEGORY BULK ADD::SUCCESS:: DETAILS::{\"inserted\":1,\"skipped\":0,\"error_count\":0}', 'QVhIVTdpSklpSzZNcTdoSGN4eHI3OW5IOGZWTDEydzQ5ZHBPeXRqWDdYbzFCenpETlpoT0dIODNJSy85TklRM0VRanozcityYmp6Qldod1JiUWVqYmw0V3FCeFVkMWVEaHFnRkVUMGRDU1lwSzRrbDFDVFpJejFxNzZZbVdlUGlmNFJNZ2dRcnhYOW5pTmxRbWlFaHVjWDcyUjlkc0JUc1MvQ00yUTBEVmV3cUxPU0ZSazl0cjdoSEs2NUM5akl', '2', 0),
(9, 1, '2026-03-04 14:16:04', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":2,\"general_id\":\"po_as\",\"full_name\":\"PO, PO PO\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'cnBBeXROQmhLbmJuVjJxdW1lODNnRTNvMWduOWEyMWRJYUFKMmsvU0s5cmJYbnZmZldqSEJmS3RITUpZbWs1a0tsVXhRcHNBQ29ob1ZSTkRNNjBYd3haQmVaalVHQ2F1QmxCRUJIeVpRelNYd1c2QWhhWDlzRWRuTklzZXladEpyRjZvbE5tN2FWRVEyU0Y2akMxTnBiODI0UkU2SWZzSjQ1dnJvVGtNR1FMc1UxYkcyd1lPOGFiNU50aXowRWQ', '1', 0),
(10, 1, '2026-03-05 10:30:46', 'AST CATEGORY ADD CATEGORIES::SUCCESS:: DETAILS::{\"inserted\":2,\"skipped\":0,\"error_count\":0}', 'K1ptSlhYWi9oakFyNmFXREVHRXJ0cTJ5QXJLem1JUmd6VWpoaEljS0p3YVBnakJvQ0NpWU9SbThLUE1BVVN2TFZWeGRJeXUrdHB2NUZpR1NlNi9FOCs3cys3QjQ2ZXRsWlg0TDJVUlBpM2pHUmp3cVdkWHM4aytmYVM0Z0hLdXFmeE1CYzFsSGFleHJSWEV0NldNb3ZQb0lWS3BpcWVBUjNhNXhPSkhoWml6UDVoejRESG8vTWFuMTBDRE9xcjZ', '2', 0),
(11, 1, '2026-03-05 10:36:34', 'AST CATEGORY ADD CATEGORIES::SUCCESS:: DETAILS::{\"inserted\":2,\"skipped\":1,\"added\":[\"actlogdettest\",\"actlogdettest1\"],\"not_added\":[\"actlogdettest1 (duplicate)\"]}', 'K1ptSlhYWi9oakFyNmFXREVHRXJ0cTJ5QXJLem1JUmd6VWpoaEljS0p3YVBnakJvQ0NpWU9SbThLUE1BVVN2TFZWeGRJeXUrdHB2NUZpR1NlNi9FOCs3cys3QjQ2ZXRsWlg0TDJVUlBpM2pHUmp3cVdkWHM4aytmYVM0Z0hLdXFmeE1CYzFsSGFleHJSWEV0NldNb3ZQb0lWS3BpcWVBUjNhNXhPSkhoWml6UDVoejRESG8vTWFuMTBDRE9xcjZ', '2', 0),
(12, 1, '2026-03-05 10:54:20', 'AST ADD ITEM::SUCCESS:: DETAILS::{\"property_code_start\":\"AST-TEST-0004\",\"property_code_end\":\"AST-TEST-0004\",\"property_number\":\"TEST\",\"property_series_start\":4,\"units_created\":1,\"category_id\":43,\"item_description\":\"etset\",\"serial_numbers_provided\":0,\"quantity_per_unit\":1,\"available_qty\":1,\"unit\":\"pcs\",\"source_of_fund\":\"\",\"cost_value\":null,\"allowed_status\":null,\"is_available\":1}', 'NmNxeXlSbmdPWWprNmdlRDIxTEhJUTZWdHc4QW9WRk5pakhMTXN4S1p2TlBIVkFjOTVIQytvdG9aOG5kQXNvd21zUkUxdVMvMzdKcldKSEV2Qk9talJ4MkN4Ri9nM0J5dzg1VGhPRW5qSkxkRVl3OEEyazF4L2NMWi82dER0SnRHTmFMNTd4YTdqVHJVOXFqSkNJSXg1S0dNVUFPa2lmSlkvOWlUTitUNWNqa2QwUFgzTTRJdmxubFF3TUkzTld', '2', 0),
(13, 1, '2026-03-05 11:00:38', 'AST ADD ITEM::SUCCESS:: DETAILS::{\"property_code_start\":\"AST-HAHA-0001\",\"property_code_end\":\"AST-HAHA-0001\",\"property_number\":\"HAHA\",\"property_series_start\":1,\"units_created\":1,\"category_id\":43,\"item_description\":\"asdfasd\",\"serial_numbers_provided\":0,\"quantity_per_unit\":1,\"available_qty\":1,\"unit\":\"pcs\",\"source_of_fund\":\"\",\"cost_value\":null,\"allowed_status\":null,\"is_available\":1}', 'NmNxeXlSbmdPWWprNmdlRDIxTEhJUTZWdHc4QW9WRk5pakhMTXN4S1p2TlBIVkFjOTVIQytvdG9aOG5kQXNvd21zUkUxdVMvMzdKcldKSEV2Qk9talJ4MkN4Ri9nM0J5dzg1VGhPRW5qSkxkRVl3OEEyazF4L2NMWi82dER0SnRHTmFMNTd4YTdqVHJVOXFqSkNJSXg1S0dNVUFPa2lmSlkvOWlUTitUNWNqa2QwUFgzTTRJdmxubFF3TUkzTld', '2', 0),
(14, 1, '2026-03-05 16:46:35', 'AST SET AVAILABLE RULES::SUCCESS:: DETAILS::{\"property_code\":\"AST-HAHA-0001\",\"old_available_qty\":1,\"new_available_qty\":1,\"old_allowed_status\":null,\"new_allowed_status\":\"{\\\"teaching\\\":[1,2,3],\\\"non_teaching\\\":[]}\",\"old_is_available\":1,\"new_is_available\":1}', 'WWMyWmgvVjZ2OFM1aTdIcWhuV29GeXh5bFcrMStnNXJ4MUtObmg4bEpYdzMvWUsrZXNhdkQ3NW94TjJDTktnMzhVMWlMTzJFNjFVSk0raGtYajVqalJYQlR0dHcxelU2MkRrYncxcVlzVlhrdjR1WjBITEc0czZxMGVoanJWeGo2ejFuQmtQZ1YxZVhGOHdIcEplZ1ZXOFo3UkdhakJrYnphNUpadzRvUy83WFp4R2ZjemhYY2tBZUR4RXBaWE9', '2', 0),
(15, 1, '2026-03-05 16:56:25', 'AST ADD ITEM::SUCCESS:: DETAILS::{\"property_code_start\":\"AST-1234-0004\",\"property_code_end\":\"AST-1234-0004\",\"property_number\":\"1234\",\"property_series_start\":4,\"units_created\":1,\"category_id\":33,\"item_description\":\"1234\",\"serial_numbers_provided\":0,\"quantity_per_unit\":1,\"available_qty\":1,\"unit\":\"pcs\",\"source_of_fund\":\"\",\"cost_value\":null,\"allowed_status\":null,\"is_available\":1}', 'WWMyWmgvVjZ2OFM1aTdIcWhuV29GeXh5bFcrMStnNXJ4MUtObmg4bEpYdzMvWUsrZXNhdkQ3NW94TjJDTktnMzhVMWlMTzJFNjFVSk0raGtYajVqalJYQlR0dHcxelU2MkRrYncxcVlzVlhrdjR1WjBITEc0czZxMGVoanJWeGo2ejFuQmtQZ1YxZVhGOHdIcEplZ1ZXOFo3UkdhakJrYnphNUpadzRvUy83WFp4R2ZjemhYY2tBZUR4RXBaWE9', '2', 0),
(16, 1, '2026-03-05 16:58:41', 'AST ADD ITEM::SUCCESS:: DETAILS::{\"property_code_start\":\"AST-1234-0005\",\"property_code_end\":\"AST-1234-0005\",\"property_number\":\"1234\",\"property_series_start\":5,\"units_created\":1,\"category_id\":12,\"item_description\":\"1234\",\"serial_numbers_provided\":1,\"quantity_per_unit\":1,\"available_qty\":1,\"unit\":\"pcs\",\"source_of_fund\":\"\",\"cost_value\":null,\"allowed_status\":null,\"is_available\":1}', 'WWMyWmgvVjZ2OFM1aTdIcWhuV29GeXh5bFcrMStnNXJ4MUtObmg4bEpYdzMvWUsrZXNhdkQ3NW94TjJDTktnMzhVMWlMTzJFNjFVSk0raGtYajVqalJYQlR0dHcxelU2MkRrYncxcVlzVlhrdjR1WjBITEc0czZxMGVoanJWeGo2ejFuQmtQZ1YxZVhGOHdIcEplZ1ZXOFo3UkdhakJrYnphNUpadzRvUy83WFp4R2ZjemhYY2tBZUR4RXBaWE9', '2', 0),
(17, 1, '2026-03-05 17:06:13', 'AST ADD ITEM::SUCCESS:: DETAILS::{\"property_code_start\":\"AST-1234-0006\",\"property_code_end\":\"AST-1234-0006\",\"property_number\":\"1234\",\"property_series_start\":6,\"units_created\":1,\"category_id\":12,\"item_description\":\"1234\",\"serial_numbers_provided\":1,\"quantity_per_unit\":1,\"available_qty\":0,\"unit\":\"pcs\",\"source_of_fund\":\"\",\"cost_value\":null,\"allowed_status\":\"{\\\"none\\\":true}\",\"is_available\":0}', 'WWMyWmgvVjZ2OFM1aTdIcWhuV29GeXh5bFcrMStnNXJ4MUtObmg4bEpYdzMvWUsrZXNhdkQ3NW94TjJDTktnMzhVMWlMTzJFNjFVSk0raGtYajVqalJYQlR0dHcxelU2MkRrYncxcVlzVlhrdjR1WjBITEc0czZxMGVoanJWeGo2ejFuQmtQZ1YxZVhGOHdIcEplZ1ZXOFo3UkdhakJrYnphNUpadzRvUy83WFp4R2ZjemhYY2tBZUR4RXBaWE9', '2', 0),
(18, 1, '2026-03-05 17:07:00', 'AST BULK SET AVAILABLE RULES::SUCCESS:: DETAILS::{\"count\":50,\"property_codes\":[\"AST-1234-0006\",\"AST-1234-0005\",\"AST-1234-0004\",\"AST-HAHA-0001\",\"AST-TEST-0004\",\"AST-TEST-0001\",\"AST-TEST-0002\",\"AST-TEST-0003\",\"AST-1234-0001\",\"AST-1234-0002\",\"AST-1234-0003\",\"AST-JIPRE-0001\",\"AST-JIPRE-0002\",\"AST-JIPRE-0003\",\"AST-ASDF351-0001\",\"AST-0321-0001\",\"AST-UNITTEST-0003\",\"AST-UNITTEST-0002\",\"AST-UNITTEST-0001\",\"AST-HAHAHATESTINGLANGPO123456789-0001\",\"AST-0000-0019\",\"AST-0000-0018\",\"AST-1111-0001\",\"AST-3452-0001\",\"AST-8946-0001\",\"AST-0654-0001\",\"AST-0000-0017\",\"AST-0000-0016\",\"AST-0000-0015\",\"AST-0000-0014\",\"AST-0000-0013\",\"AST-0001-0007\",\"AST-0000-0012\",\"AST-0001-0006\",\"AST-0001-0005\",\"AST-0001-0004\",\"AST-0000-0011\",\"AST-0001-0003\",\"AST-0000-0010\",\"AST-0000-0009\",\"AST-0000-0008\",\"AST-0000-0007\",\"AST-0000-0006\",\"AST-0000-0004\",\"AST-0000-0003\",\"AST-0000-0002\",\"AST-0000-0001\",\"AST-0001-0002\",\"AST-6767-0001\",\"AST-0001-0001\"],\"available_qty\":null,\"allowed_status\":\"{\\\"none\\\":true}\"}', 'WWMyWmgvVjZ2OFM1aTdIcWhuV29GeXh5bFcrMStnNXJ4MUtObmg4bEpYdzMvWUsrZXNhdkQ3NW94TjJDTktnMzhVMWlMTzJFNjFVSk0raGtYajVqalJYQlR0dHcxelU2MkRrYncxcVlzVlhrdjR1WjBITEc0czZxMGVoanJWeGo2ejFuQmtQZ1YxZVhGOHdIcEplZ1ZXOFo3UkdhakJrYnphNUpadzRvUy83WFp4R2ZjemhYY2tBZUR4RXBaWE9', '2', 0),
(19, 1, '2026-03-05 17:25:04', 'REQUISITION DISAPPROVAL::SUCCESS:: DETAILS::{\"requisition_id\":1,\"module_type\":\"AST\",\"item_code\":\"AST-0000-0011\",\"item_description\":\"sfgsdfg\",\"qty_requested\":1,\"requester_user_id\":5,\"reason\":\"ayoko\",\"old_status\":\"approved\",\"new_status\":\"disapproved\"}', 'WWMyWmgvVjZ2OFM1aTdIcWhuV29GeXh5bFcrMStnNXJ4MUtObmg4bEpYdzMvWUsrZXNhdkQ3NW94TjJDTktnMzhVMWlMTzJFNjFVSk0raGtYajVqalJYQlR0dHcxelU2MkRrYncxcVlzVlhrdjR1WjBITEc0czZxMGVoanJWeGo2ejFuQmtQZ1YxZVhGOHdIcEplZ1ZXOFo3UkdhakJrYnphNUpadzRvUy83WFp4R2ZjemhYY2tBZUR4RXBaWE9', '2', 0),
(20, 1, '2026-03-09 09:37:08', 'FACILITY CREATE::SUCCESS:: DETAILS::{\"facility_code\":\"MISD\",\"facility_name\":\"Management Information System Department\"}', 'cnpUUDNwbW1rdVBnWXdidmh1S0hCOVIxUzZSdCtqdi8xZlVkczBYdTdQRzBwVHI5MXMvMW45VThSb05pUnpWNTVnUkZOR2Jyd2VvK25ZdUJrdlFoUnNTMFY5SFprQmlueXZnenp3YSs3NlFsN044UjZLOXFIRFQ1QVAzTlBQVkMzMWEwemxoRkdpM0t5UEZkeXcxNnpGOVRuNksrZGlmN29KZTRJRWRjKy9UazY1LzlCeW9LQlNIS05SeXJPbEZ', '2', 0),
(21, 1, '2026-03-09 09:39:03', 'FACILITY UPDATE::SUCCESS:: DETAILS::{\"facility_id\":1,\"facility_code\":\"AB\",\"facility_name\":\"Admin Building\"}', 'cnpUUDNwbW1rdVBnWXdidmh1S0hCOVIxUzZSdCtqdi8xZlVkczBYdTdQRzBwVHI5MXMvMW45VThSb05pUnpWNTVnUkZOR2Jyd2VvK25ZdUJrdlFoUnNTMFY5SFprQmlueXZnenp3YSs3NlFsN044UjZLOXFIRFQ1QVAzTlBQVkMzMWEwemxoRkdpM0t5UEZkeXcxNnpGOVRuNksrZGlmN29KZTRJRWRjKy9UazY1LzlCeW9LQlNIS05SeXJPbEZ', '2', 0),
(22, 1, '2026-03-09 09:39:40', 'FACILITY UNIT CREATE::SUCCESS:: DETAILS::{\"facility_id\":1,\"unit_code\":\"MISD\",\"unit_name\":\"Management Information System Department\"}', 'cnpUUDNwbW1rdVBnWXdidmh1S0hCOVIxUzZSdCtqdi8xZlVkczBYdTdQRzBwVHI5MXMvMW45VThSb05pUnpWNTVnUkZOR2Jyd2VvK25ZdUJrdlFoUnNTMFY5SFprQmlueXZnenp3YSs3NlFsN044UjZLOXFIRFQ1QVAzTlBQVkMzMWEwemxoRkdpM0t5UEZkeXcxNnpGOVRuNksrZGlmN29KZTRJRWRjKy9UazY1LzlCeW9LQlNIS05SeXJPbEZ', '2', 0),
(23, 1, '2026-03-09 09:42:15', 'AST BULK SET AVAILABLE RULES::SUCCESS:: DETAILS::{\"count\":50,\"property_codes\":[\"AST-1234-0006\",\"AST-1234-0005\",\"AST-1234-0004\",\"AST-HAHA-0001\",\"AST-TEST-0004\",\"AST-TEST-0001\",\"AST-TEST-0002\",\"AST-TEST-0003\",\"AST-1234-0001\",\"AST-1234-0002\",\"AST-1234-0003\",\"AST-JIPRE-0001\",\"AST-JIPRE-0002\",\"AST-JIPRE-0003\",\"AST-ASDF351-0001\",\"AST-0321-0001\",\"AST-UNITTEST-0003\",\"AST-UNITTEST-0002\",\"AST-UNITTEST-0001\",\"AST-HAHAHATESTINGLANGPO123456789-0001\",\"AST-0000-0019\",\"AST-0000-0018\",\"AST-1111-0001\",\"AST-3452-0001\",\"AST-8946-0001\",\"AST-0654-0001\",\"AST-0000-0017\",\"AST-0000-0016\",\"AST-0000-0015\",\"AST-0000-0014\",\"AST-0000-0013\",\"AST-0001-0007\",\"AST-0000-0012\",\"AST-0001-0006\",\"AST-0001-0005\",\"AST-0001-0004\",\"AST-0000-0011\",\"AST-0001-0003\",\"AST-0000-0010\",\"AST-0000-0009\",\"AST-0000-0008\",\"AST-0000-0007\",\"AST-0000-0006\",\"AST-0000-0004\",\"AST-0000-0003\",\"AST-0000-0002\",\"AST-0000-0001\",\"AST-0001-0002\",\"AST-6767-0001\",\"AST-0001-0001\"],\"available_qty\":null,\"allowed_status\":\"{\\\"teaching\\\":[1],\\\"non_teaching\\\":[]}\"}', 'cnpUUDNwbW1rdVBnWXdidmh1S0hCOVIxUzZSdCtqdi8xZlVkczBYdTdQRzBwVHI5MXMvMW45VThSb05pUnpWNTVnUkZOR2Jyd2VvK25ZdUJrdlFoUnNTMFY5SFprQmlueXZnenp3YSs3NlFsN044UjZLOXFIRFQ1QVAzTlBQVkMzMWEwemxoRkdpM0t5UEZkeXcxNnpGOVRuNksrZGlmN29KZTRJRWRjKy9UazY1LzlCeW9LQlNIS05SeXJPbEZ', '2', 0),
(24, 1, '2026-03-09 09:42:46', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":3,\"general_id\":\"user\",\"full_name\":\"USER, USER USER\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'VmowRTJIdlJsaUNCdEMvNHI3YmgwaVlSVE1Hbk5GcHBDY3A3bTFOd0JJRjRURU5LTWRhTnlvS0lKdGJDWGZFZWdKNTd2dFpjRmtIRStKeis5RXJ0UjJvbGhpTnNMckE5alNtK0xFdlJ6emtZa1cyREk2SDhmWWdmaTQ5TEJ0OE9qY2RjT0lYSDBGWmorRmM5RFp4NEhkNEhDWXIrWXhzN3ZoZ3F3MDhyK0phZFFRZnpEaUxIblpnczg2RlA5dUY', '1', 0),
(25, 3, '2026-03-09 09:43:21', 'USER REQUISITION SUBMIT::SUCCESS:: DETAILS::{\"module_type\":\"AST\",\"item_code\":\"AST-1234-0005\",\"qty_requested\":1}', 'QitDT3kwZ3hyZUNEZzVYSXMzNzhCV2plL2VCQS9SRlcrbnpkNFJONk83Z0Q1eU50RVBWMUZMVlJrSEkwYVR6LzJYN3h0Z0l0N1doNFcvVDJ3SXUybnlmYjhNd1BYYm9LSDFxNG9YYkpoUkd2bkp3NW5vM0dOTzVQUlBCL20xSW8vN3o3V0xSRHJpcE9iOHdaOWo0Q3JJY0Y5azIzbUlEaEFnNU1aakF1cFZvZ054Z0VOYkdNNHBGOWtHdmtNZTR', '4', 0),
(26, 1, '2026-03-09 09:43:43', 'REQUISITION APPROVAL::SUCCESS:: DETAILS::{\"requisition_id\":2,\"module_type\":\"AST\",\"item_code\":\"AST-1234-0005\",\"item_description\":\"1234\",\"qty_requested\":1,\"requester_user_id\":3,\"old_status\":\"pending\",\"new_status\":\"approved\",\"available_qty_before\":1,\"available_qty_after\":0}', 'd01BSlRVYldSenJHdWljdDhVdENMSXpsZFpkTTE5YlpHckFUTzU2a0ovUzVZS1grTzlmTkk4RVQ3ZzhpblR2K1R1ZUlTdVQzR2R5bGVBWmVJVFk3VHA3MFkzL2xuTDd5RjlsT05oVWthd25OWXBkbUg1OWNiQzFVaEJwV1NnVFpBRVJHSitzUE5ZL3MyTzkyNDJlRVA2QjkwZVpRd2Exa0NHZUdJR3d2K1RYS3Z0MXBXN3dBL3FualJGcDZ3Tzh', '2', 0),
(27, 1, '2026-03-09 09:44:42', 'FACILITY ITEM ASSIGN::SUCCESS:: DETAILS::{\"assignment_id\":1,\"facility_id\":1,\"unit_id\":1,\"module_type\":\"AST\",\"item_code\":\"AST-0000-0001\",\"qty\":1}', 'd01BSlRVYldSenJHdWljdDhVdENMSXpsZFpkTTE5YlpHckFUTzU2a0ovUzVZS1grTzlmTkk4RVQ3ZzhpblR2K1R1ZUlTdVQzR2R5bGVBWmVJVFk3VHA3MFkzL2xuTDd5RjlsT05oVWthd25OWXBkbUg1OWNiQzFVaEJwV1NnVFpBRVJHSitzUE5ZL3MyTzkyNDJlRVA2QjkwZVpRd2Exa0NHZUdJR3d2K1RYS3Z0MXBXN3dBL3FualJGcDZ3Tzh', '2', 0),
(28, 1, '2026-03-09 09:44:47', 'FACILITY ASSIGNMENT STATUS::SUCCESS:: DETAILS::{\"assignment_id\":1,\"old_status\":\"ACTIVE\",\"new_status\":\"REPORTED\"}', 'd01BSlRVYldSenJHdWljdDhVdENMSXpsZFpkTTE5YlpHckFUTzU2a0ovUzVZS1grTzlmTkk4RVQ3ZzhpblR2K1R1ZUlTdVQzR2R5bGVBWmVJVFk3VHA3MFkzL2xuTDd5RjlsT05oVWthd25OWXBkbUg1OWNiQzFVaEJwV1NnVFpBRVJHSitzUE5ZL3MyTzkyNDJlRVA2QjkwZVpRd2Exa0NHZUdJR3d2K1RYS3Z0MXBXN3dBL3FualJGcDZ3Tzh', '2', 0),
(29, 1, '2026-03-09 09:46:44', 'AST ADD ITEM::SUCCESS:: DETAILS::{\"property_code_start\":\"AST-FASDF-0001\",\"property_code_end\":\"AST-FASDF-0001\",\"property_number\":\"FASDF\",\"property_series_start\":1,\"units_created\":1,\"category_id\":12,\"item_description\":\"asdf\",\"serial_numbers_provided\":1,\"quantity_per_unit\":1,\"available_qty\":0,\"unit\":\"pcs\",\"source_of_fund\":\"\",\"cost_value\":null,\"allowed_status\":\"{\\\"none\\\":true}\",\"is_available\":0}', 'd01BSlRVYldSenJHdWljdDhVdENMSXpsZFpkTTE5YlpHckFUTzU2a0ovUzVZS1grTzlmTkk4RVQ3ZzhpblR2K1R1ZUlTdVQzR2R5bGVBWmVJVFk3VHA3MFkzL2xuTDd5RjlsT05oVWthd25OWXBkbUg1OWNiQzFVaEJwV1NnVFpBRVJHSitzUE5ZL3MyTzkyNDJlRVA2QjkwZVpRd2Exa0NHZUdJR3d2K1RYS3Z0MXBXN3dBL3FualJGcDZ3Tzh', '2', 0),
(30, 3, '2026-03-09 10:23:16', 'USER REQUISITION SUBMIT::SUCCESS:: DETAILS::{\"module_type\":\"AST\",\"item_code\":\"AST-1234-0004\",\"qty_requested\":1}', 'QitDT3kwZ3hyZUNEZzVYSXMzNzhCV2plL2VCQS9SRlcrbnpkNFJONk83Z0Q1eU50RVBWMUZMVlJrSEkwYVR6LzJYN3h0Z0l0N1doNFcvVDJ3SXUybnlmYjhNd1BYYm9LSDFxNG9YYkpoUkd2bkp3NW5vM0dOTzVQUlBCL20xSW8vN3o3V0xSRHJpcE9iOHdaOWo0Q3JJY0Y5azIzbUlEaEFnNU1aakF1cFZvZ054Z0VOYkdNNHBGOWtHdmtNZTR', '4', 0),
(31, 1, '2026-03-09 10:24:06', 'REQUISITION APPROVAL::SUCCESS:: DETAILS::{\"requisition_id\":3,\"module_type\":\"AST\",\"item_code\":\"AST-1234-0004\",\"item_description\":\"1234\",\"qty_requested\":1,\"requester_user_id\":3,\"old_status\":\"pending\",\"new_status\":\"approved\",\"available_qty_before\":1,\"available_qty_after\":0}', 'd01BSlRVYldSenJHdWljdDhVdENMSXpsZFpkTTE5YlpHckFUTzU2a0ovUzVZS1grTzlmTkk4RVQ3ZzhpblR2K1R1ZUlTdVQzR2R5bGVBWmVJVFk3VHA3MFkzL2xuTDd5RjlsT05oVWthd25OWXBkbUg1OWNiQzFVaEJwV1NnVFpBRVJHSitzUE5ZL3MyTzkyNDJlRVA2QjkwZVpRd2Exa0NHZUdJR3d2K1RYS3Z0MXBXN3dBL3FualJGcDZ3Tzh', '2', 0),
(32, 1, '2026-03-09 10:24:34', 'REQUISITION CLAIM::SUCCESS:: DETAILS::{\"requisition_id\":3,\"assignment_id\":2,\"facility_id\":1,\"unit_id\":1,\"module_type\":\"AST\",\"item_code\":\"AST-1234-0004\",\"qty_requested\":1,\"issued_to_user_id\":null,\"accountable_user_id\":null,\"managed_by_user_id\":null}', 'd01BSlRVYldSenJHdWljdDhVdENMSXpsZFpkTTE5YlpHckFUTzU2a0ovUzVZS1grTzlmTkk4RVQ3ZzhpblR2K1R1ZUlTdVQzR2R5bGVBWmVJVFk3VHA3MFkzL2xuTDd5RjlsT05oVWthd25OWXBkbUg1OWNiQzFVaEJwV1NnVFpBRVJHSitzUE5ZL3MyTzkyNDJlRVA2QjkwZVpRd2Exa0NHZUdJR3d2K1RYS3Z0MXBXN3dBL3FualJGcDZ3Tzh', '2', 0),
(33, 1, '2026-03-09 11:00:57', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":13,\"general_id\":\"2026-0010\",\"full_name\":\"LIM, ROBERT JOHN\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(34, 1, '2026-03-09 11:01:04', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":12,\"general_id\":\"2026-0009\",\"full_name\":\"NAVARRO, PATRICIA JOY\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(35, 1, '2026-03-09 11:01:08', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":11,\"general_id\":\"2026-0008\",\"full_name\":\"DELOS SANTOS, MARK ANTHONY\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(36, 1, '2026-03-09 11:01:11', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":10,\"general_id\":\"2026-0007\",\"full_name\":\"CASTRO, ISABELLA MARIE\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(37, 1, '2026-03-09 11:01:15', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":9,\"general_id\":\"2026-0006\",\"full_name\":\"MENDOZA, DAVID LEE\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(38, 1, '2026-03-09 11:01:18', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":8,\"general_id\":\"2026-0005\",\"full_name\":\"RAMOS, SOPHIA ANNE\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(39, 1, '2026-03-09 11:01:22', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":7,\"general_id\":\"2026-0004\",\"full_name\":\"GARCIA, MICHAEL JAMES\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(40, 1, '2026-03-09 11:01:29', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":6,\"general_id\":\"2026-0003\",\"full_name\":\"LOPEZ, EMILY GRACE\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(41, 1, '2026-03-09 11:01:33', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":5,\"general_id\":\"2026-0002\",\"full_name\":\"SANTOS, JUAN CRUZ\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(42, 1, '2026-03-09 11:01:38', 'UPDATE EMPLOYMENT STATUS:: DETAILS::{\"user_id\":4,\"general_id\":\"2026-0001\",\"full_name\":\"REYES, ANNA MARIA\",\"old_employment_status_id\":0,\"new_employment_status_id\":1}', 'Rk15cVNXZzVtWU8rRnNWbzV5dGhlODJsQ1hzaU1XMmFjMnUzMnFkT1lVLys0ZTRuRmdiWTNhVTlsbWVRWXJPNTZtTUkrdnNwMXJUdlRHWlN3c1JsMTk5bXM2dmVlVWxWRHlDUHdhUHJHVG5CVlh0K2tjZHNiQjRVeHA3NjBDUHJXdk91N0hiMFFnSkMzYXVsRHNOK0ZMakQ2NmRpVEREMUNPMkRNQjhmM2xYbUliWkVUZXlIN3VkSS9SUUYzT0N', '1', 0),
(43, 1, '2026-03-09 11:03:29', 'FACILITY CREATE::SUCCESS:: DETAILS::{\"facility_code\":\"TEST\",\"facility_name\":\"FOR TESTING\"}', 'dnZKbDgrK29oWjUwZVovNDk1SVVpcGxoZWFOVmQzYVByclI0NFpWdSs2TW1DSzVzSVFpN2FHd0dNSjgzMVhLbllqRTFwMS9ick9XaXVpM1hVZWJHSkJtS1c2V1A5TklFV0VEYzhHQldPTUxBb0FwMTJuMDYvVDNzWWxWUkFTQjFpL0tQRWhhbmNCV3ZRU20zRXE3YmZaNmtnZlNMSUkzaXU5TG54a284anlLRXlqQm9UUHlkTSt2TC9VU1poWjB', '2', 0),
(44, 1, '2026-03-09 11:11:07', 'FACILITY UNIT CREATE::SUCCESS:: DETAILS::{\"facility_id\":2,\"unit_code\":\"TEST\",\"unit_name\":\"test\",\"facility_unit_manager_user_id\":3}', 'dnZKbDgrK29oWjUwZVovNDk1SVVpcGxoZWFOVmQzYVByclI0NFpWdSs2TW1DSzVzSVFpN2FHd0dNSjgzMVhLbllqRTFwMS9ick9XaXVpM1hVZWJHSkJtS1c2V1A5TklFV0VEYzhHQldPTUxBb0FwMTJuMDYvVDNzWWxWUkFTQjFpL0tQRWhhbmNCV3ZRU20zRXE3YmZaNmtnZlNMSUkzaXU5TG54a284anlLRXlqQm9UUHlkTSt2TC9VU1poWjB', '2', 0),
(45, 1, '2026-03-09 11:16:05', 'FACILITY UNIT UPDATE::SUCCESS:: DETAILS::{\"unit_id\":2,\"facility_id\":2,\"unit_code\":\"TEST\",\"unit_name\":\"test\",\"facility_unit_manager_user_id\":3}', 'dnZKbDgrK29oWjUwZVovNDk1SVVpcGxoZWFOVmQzYVByclI0NFpWdSs2TW1DSzVzSVFpN2FHd0dNSjgzMVhLbllqRTFwMS9ick9XaXVpM1hVZWJHSkJtS1c2V1A5TklFV0VEYzhHQldPTUxBb0FwMTJuMDYvVDNzWWxWUkFTQjFpL0tQRWhhbmNCV3ZRU20zRXE3YmZaNmtnZlNMSUkzaXU5TG54a284anlLRXlqQm9UUHlkTSt2TC9VU1poWjB', '2', 0),
(46, 1, '2026-03-09 13:50:51', 'AST ADD ITEM::SUCCESS:: DETAILS::{\"property_code_start\":\"AST-QWER-0001\",\"property_code_end\":\"AST-QWER-0001\",\"property_number\":\"QWER\",\"property_series_start\":1,\"units_created\":1,\"category_id\":12,\"item_description\":\"qwer\",\"serial_numbers_provided\":0,\"quantity_per_unit\":1,\"available_qty\":0,\"unit\":\"pcs\",\"source_of_fund\":\"\",\"cost_value\":null,\"allowed_status\":\"{\\\"none\\\":true}\",\"is_available\":0}', 'Mk9jQ1Q0dUVnK2NMMVZQSW9aUGF1YVF3djhVWTNzMXVLS1FHRjVEWk92RHZmVXVZaVZqZnc4UW0veVNsc29LUVVTVVBOendZc3EwNzhaMEszUXlMMnRpM09XQVVzZDlHZnVjSWFRa3pRWXEzNVNKaDhCZnRpNFg2SldYSVJXckVsVVIwUk1lMkxIZkxpTjY4UzR0M0JubHFZMDVJNlRIc0ZmWlQzTVdtZUxEYmZ1SDdkOGcxTUhiQ3JBeUZXdVM', '2', 0),
(47, 1, '2026-03-09 13:58:41', 'FACILITY CREATE::SUCCESS:: DETAILS::{\"facility_code\":\"JMC 1\",\"facility_name\":\"JMC Building 1st Floor\"}', 'Mk9jQ1Q0dUVnK2NMMVZQSW9aUGF1YVF3djhVWTNzMXVLS1FHRjVEWk92RHZmVXVZaVZqZnc4UW0veVNsc29LUVVTVVBOendZc3EwNzhaMEszUXlMMnRpM09XQVVzZDlHZnVjSWFRa3pRWXEzNVNKaDhCZnRpNFg2SldYSVJXckVsVVIwUk1lMkxIZkxpTjY4UzR0M0JubHFZMDVJNlRIc0ZmWlQzTVdtZUxEYmZ1SDdkOGcxTUhiQ3JBeUZXdVM', '2', 0),
(48, 1, '2026-03-09 13:59:05', 'FACILITY CREATE::SUCCESS:: DETAILS::{\"facility_code\":\"JMC 2\",\"facility_name\":\"JMC Building 2nd Floor\"}', 'Mk9jQ1Q0dUVnK2NMMVZQSW9aUGF1YVF3djhVWTNzMXVLS1FHRjVEWk92RHZmVXVZaVZqZnc4UW0veVNsc29LUVVTVVBOendZc3EwNzhaMEszUXlMMnRpM09XQVVzZDlHZnVjSWFRa3pRWXEzNVNKaDhCZnRpNFg2SldYSVJXckVsVVIwUk1lMkxIZkxpTjY4UzR0M0JubHFZMDVJNlRIc0ZmWlQzTVdtZUxEYmZ1SDdkOGcxTUhiQ3JBeUZXdVM', '2', 0),
(49, 1, '2026-03-09 13:59:20', 'FACILITY CREATE::SUCCESS:: DETAILS::{\"facility_code\":\"JMC 3\",\"facility_name\":\"JMC Building 3rd Floor\"}', 'Mk9jQ1Q0dUVnK2NMMVZQSW9aUGF1YVF3djhVWTNzMXVLS1FHRjVEWk92RHZmVXVZaVZqZnc4UW0veVNsc29LUVVTVVBOendZc3EwNzhaMEszUXlMMnRpM09XQVVzZDlHZnVjSWFRa3pRWXEzNVNKaDhCZnRpNFg2SldYSVJXckVsVVIwUk1lMkxIZkxpTjY4UzR0M0JubHFZMDVJNlRIc0ZmWlQzTVdtZUxEYmZ1SDdkOGcxTUhiQ3JBeUZXdVM', '2', 0),
(50, 1, '2026-03-09 14:35:59', 'FACILITY ASSIGNMENT STATUS::SUCCESS:: DETAILS::{\"assignment_id\":2,\"old_status\":\"ACTIVE\",\"new_status\":\"REPORTED\"}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(51, 1, '2026-03-09 15:18:55', 'FACILITY UNIT UPDATE::SUCCESS:: DETAILS::{\"unit_id\":1,\"facility_id\":1,\"unit_code\":\"MISD\",\"unit_name\":\"Management Information System Department\",\"facility_unit_manager_user_id\":3}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(52, 1, '2026-03-09 15:34:32', 'AST SET AVAILABLE RULES::SUCCESS:: DETAILS::{\"property_code\":\"AST-1234-0006\",\"old_available_qty\":0,\"new_available_qty\":1,\"old_allowed_status\":\"{\\\"teaching\\\":[1],\\\"non_teaching\\\":[]}\",\"new_allowed_status\":\"{\\\"teaching\\\":[1],\\\"non_teaching\\\":[]}\",\"old_is_available\":0,\"new_is_available\":1}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(53, 1, '2026-03-09 16:13:36', 'AST CATEGORY ADD CATEGORIES::SUCCESS:: DETAILS::{\"inserted\":1,\"skipped\":0,\"added\":[\"removed avb qty\"]}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(54, 1, '2026-03-09 16:14:14', 'AST ADD ITEM::SUCCESS:: DETAILS::{\"property_code_start\":\"AST-ASDFG-0001\",\"property_code_end\":\"AST-ASDFG-0001\",\"property_number\":\"ASDFG\",\"property_series_start\":1,\"units_created\":1,\"category_id\":45,\"item_description\":\"asdgeqrgh\",\"serial_numbers_provided\":1,\"quantity_per_unit\":1,\"unit\":\"pcs\",\"source_of_fund\":\"\",\"cost_value\":null,\"allowed_status\":\"{\\\"none\\\":true}\",\"is_available\":0}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(55, 1, '2026-03-09 16:19:13', 'AST SET AVAILABLE RULES::SUCCESS:: DETAILS::{\"property_code\":\"AST-ASDFG-0001\",\"old_allowed_status\":\"{\\\"none\\\":true}\",\"new_allowed_status\":\"{\\\"teaching\\\":[1],\\\"non_teaching\\\":[]}\",\"old_is_available\":0,\"new_is_available\":1}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(56, 1, '2026-03-09 16:19:40', 'AST SET AVAILABLE RULES::SUCCESS:: DETAILS::{\"property_code\":\"AST-ASDFG-0001\",\"old_allowed_status\":\"{\\\"teaching\\\":[1],\\\"non_teaching\\\":[]}\",\"new_allowed_status\":\"{\\\"teaching\\\":[1,2,3],\\\"non_teaching\\\":[]}\",\"old_is_available\":1,\"new_is_available\":1}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(57, 1, '2026-03-09 16:21:05', 'FACILITY ITEM ASSIGN::SUCCESS:: DETAILS::{\"assignment_id\":3,\"facility_id\":2,\"unit_id\":2,\"module_type\":\"AST\",\"item_code\":\"AST-ASDFG-0001\",\"qty\":1,\"issued_to_user_id\":0,\"accountable_user_id\":0,\"managed_by_user_id\":3}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(58, 1, '2026-03-09 17:54:55', 'FACILITY ASSIGNMENT STATUS::SUCCESS:: DETAILS::{\"assignment_id\":2,\"old_status\":\"REPORTED\",\"new_status\":\"RETURNED\"}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(59, 1, '2026-03-09 17:55:00', 'FACILITY ASSIGNMENT STATUS::SUCCESS:: DETAILS::{\"assignment_id\":2,\"old_status\":\"RETURNED\",\"new_status\":\"REPORTED\"}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(60, 1, '2026-03-09 17:55:18', 'FACILITY ITEM ASSIGN::SUCCESS:: DETAILS::{\"assignment_id\":4,\"facility_id\":2,\"unit_id\":2,\"module_type\":\"AST\",\"item_code\":\"AST-0001-0004\",\"qty\":1,\"issued_to_user_id\":3,\"accountable_user_id\":3,\"managed_by_user_id\":3}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(61, 1, '2026-03-09 18:05:32', 'AST PHYSICAL CHECK SESSION STATUS::SUCCESS:: DETAILS::{\"session_id\":1,\"series_code\":\"AST-PC-2026-001\",\"old_status\":\"Active\",\"new_status\":\"Closed\"}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(62, 1, '2026-03-09 18:05:34', 'AST PHYSICAL CHECK SESSION STATUS::SUCCESS:: DETAILS::{\"session_id\":1,\"series_code\":\"AST-PC-2026-001\",\"old_status\":\"Closed\",\"new_status\":\"Closed\"}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(63, 1, '2026-03-09 18:05:35', 'AST PHYSICAL CHECK SESSION STATUS::SUCCESS:: DETAILS::{\"session_id\":1,\"series_code\":\"AST-PC-2026-001\",\"old_status\":\"Closed\",\"new_status\":\"Closed\"}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(64, 1, '2026-03-09 18:05:35', 'AST PHYSICAL CHECK SESSION STATUS::SUCCESS:: DETAILS::{\"session_id\":1,\"series_code\":\"AST-PC-2026-001\",\"old_status\":\"Closed\",\"new_status\":\"Closed\"}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(65, 1, '2026-03-09 20:52:56', 'FACILITY UNIT CREATE::SUCCESS:: DETAILS::{\"facility_id\":2,\"unit_code\":\"TEST 2\",\"unit_name\":\"test 2\",\"facility_unit_manager_user_id\":3}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0),
(66, 1, '2026-03-09 23:10:53', 'FACILITY ITEM ASSIGN::SUCCESS:: DETAILS::{\"assignment_id\":5,\"facility_id\":2,\"unit_id\":3,\"module_type\":\"AST\",\"item_code\":\"AST-JIPRE-0003\",\"qty\":1,\"issued_to_user_id\":11,\"accountable_user_id\":11,\"managed_by_user_id\":3}', 'VWtHZEIvQVdTeUg4OWQzMzllYlJBYzhDelZBMHczWEhOSUxLU0dkU21wUVZzOG9HWm9sRkhudEdBdjdNUlhZNFBBdU1ZTGN0UXB1dEtrajJhY3RxVlZudUkrVzRQeFdRQnkxVXVpRWFRdnArVFRhTzcySkdGZEMvQmdLOGlMMGJVVnh1dloyM0Juc0VvejI3bkc2OFBJU0NCLzd5NEVXT0FWY0RnaHhhTXpDSXBTL3JrWGRyQ08yY3dja2VGZ2Z', '2', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ast_audit_checks`
--

CREATE TABLE `ast_audit_checks` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `property_code` varchar(150) NOT NULL,
  `property_number` varchar(100) NOT NULL,
  `item_description` text DEFAULT NULL,
  `serial_number` varchar(150) DEFAULT NULL,
  `quantity_checked` int(11) NOT NULL DEFAULT 1,
  `unit` varchar(50) NOT NULL DEFAULT '',
  `date_stock` datetime DEFAULT NULL,
  `date_issued` date DEFAULT NULL,
  `status_at_check` varchar(100) NOT NULL DEFAULT '',
  `facility` varchar(150) NOT NULL DEFAULT '',
  `accountable` varchar(150) NOT NULL DEFAULT '',
  `issued_to` varchar(150) NOT NULL DEFAULT '',
  `managed_by` varchar(150) NOT NULL DEFAULT '',
  `condition` varchar(50) NOT NULL DEFAULT '',
  `remarks` text DEFAULT NULL,
  `checked_by` int(11) NOT NULL,
  `checked_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ast_audit_checks`
--

INSERT INTO `ast_audit_checks` (`id`, `session_id`, `property_id`, `property_code`, `property_number`, `item_description`, `serial_number`, `quantity_checked`, `unit`, `date_stock`, `date_issued`, `status_at_check`, `facility`, `accountable`, `issued_to`, `managed_by`, `condition`, `remarks`, `checked_by`, `checked_at`) VALUES
(1, 1, 21, 'AST-0001-0007', '0001', 'upuan', '', 1, 'pcs', '2026-02-13 14:36:46', NULL, '', 'Misd', 'Ako', 'Ako', 'Ako', 'Good', 'All gooods', 1, '2026-02-19 14:01:53'),
(2, 1, 17, 'AST-0001-0004', '0001', 'Malaking monitor', '', 1, 'pcs', '2026-02-13 09:36:25', NULL, '', '', '', '', '', 'Good', '', 1, '2026-02-19 11:33:39'),
(3, 1, 20, 'AST-0000-0012', '0000', 'Sofa', '', 1, 'set', '2026-02-13 10:30:59', NULL, '', 'JMC', 'Ako', 'Ako', 'Ako', 'Good', 'All goods', 1, '2026-02-19 11:35:55'),
(4, 1, 3, 'AST-0001-0002', '0001', 'computer na nag aapoy', '', 1, 'set', '2026-02-04 08:07:09', NULL, '', '', '', '', '', 'Good', '', 1, '2026-02-19 14:02:39');

-- --------------------------------------------------------

--
-- Table structure for table `ast_audit_sessions`
--

CREATE TABLE `ast_audit_sessions` (
  `id` int(11) NOT NULL,
  `series_code` varchar(50) NOT NULL,
  `audit_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','Active','Closed') NOT NULL DEFAULT 'Pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ast_audit_sessions`
--

INSERT INTO `ast_audit_sessions` (`id`, `series_code`, `audit_name`, `start_date`, `end_date`, `status`, `created_by`, `created_at`) VALUES
(1, 'AST-PC-2026-001', 'Test 1', '2026-02-18', '2026-02-20', 'Closed', 1, '2026-02-19 03:28:21');

-- --------------------------------------------------------

--
-- Table structure for table `ast_inventory`
--

CREATE TABLE `ast_inventory` (
  `item_id` int(11) NOT NULL,
  `property_number` varchar(100) NOT NULL COMMENT 'User-provided property number base',
  `property_series` int(11) NOT NULL DEFAULT 1 COMMENT 'Incremental series per property number',
  `property_code` varchar(150) NOT NULL COMMENT 'AST-[PROPERTY_NUMBER]-[SERIES] (padded)',
  `category_id` int(11) NOT NULL,
  `item_description` text NOT NULL,
  `serial_number` varchar(150) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `available_qty` int(11) DEFAULT NULL,
  `unit` varchar(50) NOT NULL DEFAULT '',
  `source_of_fund` varchar(150) DEFAULT NULL,
  `cost_value` decimal(12,2) DEFAULT NULL,
  `qr_image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `allowed_employment_status` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ast_inventory`
--

INSERT INTO `ast_inventory` (`item_id`, `property_number`, `property_series`, `property_code`, `category_id`, `item_description`, `serial_number`, `quantity`, `available_qty`, `unit`, `source_of_fund`, `cost_value`, `qr_image`, `is_available`, `allowed_employment_status`, `created_at`, `updated_at`) VALUES
(1, '0001', 1, 'AST-0001-0001', 1, 'pc set', NULL, 1, 1, 'set', NULL, NULL, NULL, 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-03 22:37:54', '2026-03-09 01:42:15'),
(2, '6767', 1, 'AST-6767-0001', 2, 'upuan na naka italic', NULL, 10, 1, 'pcs', NULL, NULL, NULL, 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-03 22:41:42', '2026-03-09 01:42:15'),
(3, '0001', 2, 'AST-0001-0002', 3, 'computer na nag aapoy', NULL, 1, 1, 'set', NULL, NULL, NULL, 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-04 00:07:09', '2026-03-09 01:42:15'),
(4, '0000', 1, 'AST-0000-0001', 6, 'asdfas', NULL, 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260205_094149_6983f55d1623b.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-04 17:41:49', '2026-03-09 01:44:42'),
(6, '0000', 2, 'AST-0000-0002', 12, 'hssdfg', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260205_155812_69844d948cde3.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-04 23:58:12', '2026-03-09 01:42:15'),
(7, '0000', 3, 'AST-0000-0003', 13, '23752', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260205_155825_69844da16bdb7.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-04 23:58:25', '2026-03-09 01:42:15'),
(8, '0000', 4, 'AST-0000-0004', 14, '5205025', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260205_155834_69844daa4c6c8.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-04 23:58:34', '2026-03-09 01:42:15'),
(10, '0000', 6, 'AST-0000-0006', 16, '000', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260205_160040_69844e289283f.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-05 00:00:40', '2026-03-09 01:42:15'),
(11, '0000', 7, 'AST-0000-0007', 17, '0000', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260205_160141_69844e659a0a1.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-05 00:01:41', '2026-03-09 01:42:15'),
(12, '0000', 8, 'AST-0000-0008', 18, '000', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260205_160148_69844e6ce01b6.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-05 00:01:48', '2026-03-09 01:42:15'),
(13, '0000', 9, 'AST-0000-0009', 19, '0000', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260205_160207_69844e7f1f718.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-05 00:02:07', '2026-03-09 01:42:15'),
(14, '0000', 10, 'AST-0000-0010', 20, '0000', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260205_160219_69844e8beb83f.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-05 00:02:19', '2026-03-09 01:42:15'),
(15, '0001', 3, 'AST-0001-0003', 21, 'keyboard', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260210_090718_698a84c68c82b.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-10 01:07:18', '2026-03-09 01:42:15'),
(16, '0000', 11, 'AST-0000-0011', 12, 'sfgsdfg', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260211_101500_698be624e6391.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-11 02:15:00', '2026-03-09 01:42:15'),
(17, '0001', 4, 'AST-0001-0004', 21, 'Malaking monitor', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260213_093625_698e80191d63a.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-13 01:36:25', '2026-03-09 09:55:17'),
(18, '0001', 5, 'AST-0001-0005', 21, 'mouse', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260213_093840_698e80a0ebccf.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-13 01:38:40', '2026-03-09 01:42:15'),
(19, '0001', 6, 'AST-0001-0006', 21, 'desktop computer', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260213_094143_698e815734cdb.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-13 01:41:43', '2026-03-09 01:42:15'),
(20, '0000', 12, 'AST-0000-0012', 22, 'Sofa', NULL, 1, 1, 'set', 'Rozz Opena', 1000000.00, 'ast_qr_20260213_103059_698e8ce3e66b7.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-13 02:30:59', '2026-03-09 01:42:15'),
(21, '0001', 7, 'AST-0001-0007', 22, 'upuan', NULL, 100, 50, 'pcs', 'ako', 10000.00, 'ast_qr_20260213_143646_698ec67e2d4aa.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-13 06:36:46', '2026-03-09 01:42:15'),
(22, '0000', 13, 'AST-0000-0013', 12, 'Testing lang ng ui', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260219_145953_6996b4e994a25.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-19 06:59:53', '2026-03-09 01:42:15'),
(23, '0000', 14, 'AST-0000-0014', 12, 'testing lang ulit ng ui hahahahahaha labyu', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260219_150550_6996b64e452c2.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-19 07:05:50', '2026-03-09 01:42:15'),
(24, '0000', 15, 'AST-0000-0015', 27, 'pusa', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260219_152230_6996ba363aaf4.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-19 07:22:30', '2026-03-09 01:42:15'),
(25, '0000', 16, 'AST-0000-0016', 27, 'test', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260219_161344_6996c638ca11d.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-19 08:13:44', '2026-03-09 01:42:15'),
(26, '0000', 17, 'AST-0000-0017', 28, 'pusa haha', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260219_162032_6996c7d01d635.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-19 08:20:32', '2026-03-09 01:42:15'),
(27, '0654', 1, 'AST-0654-0001', 29, 'asong call center', NULL, 2, 0, 'pcs', NULL, NULL, 'ast_qr_20260223_080445_699b999d20958.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-23 00:04:45', '2026-03-09 01:42:15'),
(28, '8946', 1, 'AST-8946-0001', 30, 'naka bike hahaha', NULL, 2, 0, 'pcs', NULL, NULL, 'ast_qr_20260223_080502_699b99ae26a27.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-23 00:05:02', '2026-03-09 01:42:15'),
(29, '3452', 1, 'AST-3452-0001', 2, 'wgsg', NULL, 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260223_134453_699be95580748.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-23 05:44:53', '2026-03-09 01:42:15'),
(30, '1111', 1, 'AST-1111-0001', 30, 'test aso', NULL, 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260223_152826_699c019a0d5e7.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-23 07:28:26', '2026-03-09 01:42:15'),
(31, '0000', 18, 'AST-0000-0018', 31, 'test', NULL, 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260226_113225_699fbec9023d6.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 03:32:25', '2026-03-09 01:42:15'),
(32, '0000', 19, 'AST-0000-0019', 32, 'test', NULL, 5, 0, 'pcs', NULL, NULL, 'ast_qr_20260226_113328_699fbf087a734.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 03:33:28', '2026-03-09 01:42:15'),
(33, 'HAHAHATESTINGLANGPO123456789', 1, 'AST-HAHAHATESTINGLANGPO123456789-0001', 12, 'new property number test', NULL, 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260226_135345_699fdfe968384.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 05:53:45', '2026-03-09 01:42:15'),
(34, 'UNITTEST', 1, 'AST-UNITTEST-0001', 12, 'asdfas', NULL, 1, 0, 'set', NULL, NULL, 'ast_qr_20260226_135845_699fe11539479.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 05:58:45', '2026-03-09 01:42:15'),
(35, 'UNITTEST', 2, 'AST-UNITTEST-0002', 12, 'asdfa', NULL, 1, 0, 'unittest', NULL, NULL, 'ast_qr_20260226_135901_699fe125a00aa.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 05:59:01', '2026-03-09 01:42:15'),
(36, 'UNITTEST', 3, 'AST-UNITTEST-0003', 12, 'asdf', NULL, 1, 0, 'unittest', NULL, NULL, 'ast_qr_20260226_135916_699fe13498a0c.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 05:59:16', '2026-03-09 01:42:15'),
(37, '0321', 1, 'AST-0321-0001', 12, 'testing ng mahabang description testing ng mahabang description testing ng mahabang description testing ng mahabang description testing ng mahabang description testing ng mahabang description', NULL, 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260226_141905_699fe5d9999c9.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 06:19:05', '2026-03-09 01:42:15'),
(38, 'ASDF351', 1, 'AST-ASDF351-0001', 34, 'asdfadsf', 'SN123456789', 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260226_170537_69a00ce18880f.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 09:05:37', '2026-03-09 01:42:15'),
(39, 'JIPRE', 1, 'AST-JIPRE-0001', 35, 'Jipre Baluyot', '1', 1, 1, 'pcs', 'ako', 10000.00, 'ast_qr_20260226_174122_69a0154288c9a.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 09:41:22', '2026-03-09 01:42:15'),
(40, 'JIPRE', 2, 'AST-JIPRE-0002', 35, 'Jipre Baluyot', '2', 1, 1, 'pcs', 'ako', 10000.00, 'ast_qr_20260226_174122_69a0154292ee0.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 09:41:22', '2026-03-09 01:42:15'),
(41, 'JIPRE', 3, 'AST-JIPRE-0003', 35, 'Jipre Baluyot', '3', 1, 1, 'pcs', 'ako', 10000.00, 'ast_qr_20260226_174122_69a0154294e1a.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 09:41:22', '2026-03-09 15:10:52'),
(42, '1234', 1, 'AST-1234-0001', 35, 'asdfasdf', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260226_182239_69a01eef68e3a.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 10:22:39', '2026-03-09 01:42:15'),
(43, '1234', 2, 'AST-1234-0002', 35, 'asdfasdf', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260226_182239_69a01eef72388.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 10:22:39', '2026-03-09 01:42:15'),
(44, '1234', 3, 'AST-1234-0003', 35, 'asdfasdf', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260226_182239_69a01eef88099.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-26 10:22:39', '2026-03-09 01:42:15'),
(45, 'TEST', 1, 'AST-TEST-0001', 35, 'test', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260227_093702_69a0f53e3295a.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-27 01:37:02', '2026-03-09 01:42:15'),
(46, 'TEST', 2, 'AST-TEST-0002', 35, 'test', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260227_093702_69a0f53e3788d.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-27 01:37:02', '2026-03-09 01:42:15'),
(47, 'TEST', 3, 'AST-TEST-0003', 35, 'test', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260227_093702_69a0f53e3a5e4.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-02-27 01:37:02', '2026-03-09 01:42:15'),
(48, 'TEST', 4, 'AST-TEST-0004', 43, 'etset', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260305_105418_69a8f05a94fb9.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-03-05 02:54:20', '2026-03-09 01:42:15'),
(49, 'HAHA', 1, 'AST-HAHA-0001', 43, 'asdfasd', NULL, 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260305_110034_69a8f1d2d16fc.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-03-05 03:00:38', '2026-03-09 01:42:15'),
(50, '1234', 4, 'AST-1234-0004', 33, '1234', NULL, 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260305_165623_69a94537c1ea3.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-03-05 08:56:24', '2026-03-09 09:54:55'),
(51, '1234', 5, 'AST-1234-0005', 12, '1234', '1234', 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260305_165840_69a945c002af3.png', 0, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-03-05 08:58:41', '2026-03-09 01:43:43'),
(52, '1234', 6, 'AST-1234-0006', 12, '1234', '123', 1, 1, 'pcs', NULL, NULL, 'ast_qr_20260305_170611_69a94783ce338.png', 1, '{\"teaching\":[1],\"non_teaching\":[]}', '2026-03-05 09:06:12', '2026-03-09 07:34:32'),
(53, 'FASDF', 1, 'AST-FASDF-0001', 12, 'asdf', 'asdf', 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260309_094643_69ae26831e3b8.png', 0, '{\"none\":true}', '2026-03-09 01:46:44', '2026-03-09 01:46:44'),
(54, 'QWER', 1, 'AST-QWER-0001', 12, 'qwer', NULL, 1, 0, 'pcs', NULL, NULL, 'ast_qr_20260309_135050_69ae5fba50423.png', 0, '{\"none\":true}', '2026-03-09 05:50:51', '2026-03-09 05:50:51'),
(55, 'ASDFG', 1, 'AST-ASDFG-0001', 45, 'asdgeqrgh', '123425653512354', 1, NULL, 'pcs', NULL, NULL, 'ast_qr_20260309_161413_69ae815575ffb.png', 0, '{\"teaching\":[1,2,3],\"non_teaching\":[]}', '2026-03-09 08:14:14', '2026-03-09 08:21:05');

-- --------------------------------------------------------

--
-- Table structure for table `ast_inventory_category`
--

CREATE TABLE `ast_inventory_category` (
  `category_id` int(11) NOT NULL,
  `item_category_name` varchar(150) NOT NULL,
  `category_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ast_inventory_category`
--

INSERT INTO `ast_inventory_category` (`category_id`, `item_category_name`, `category_photo`, `created_at`, `updated_at`) VALUES
(1, 'test', 'cat_20260204_141348_6982e39c7cb28.png', '2026-02-03 22:13:22', '2026-02-03 22:13:48'),
(2, 'upuang naka italic', 'cat_20260204_144034_6982e9e27ddba.jpg', '2026-02-03 22:40:34', '2026-02-03 23:02:44'),
(3, 'Computer na nag aapoy', 'cat_20260204_152808_6982f508820bb.jpg', '2026-02-03 23:28:08', '2026-02-03 23:28:08'),
(4, 'Ben 10 Computer', 'cat_20260204_152951_6982f56f446c0.jpg', '2026-02-03 23:29:51', '2026-02-03 23:29:51'),
(5, 'james na natutulog', 'cat_20260204_162615_698302a745a84.jpg', '2026-02-04 00:26:15', '2026-02-04 00:26:15'),
(6, 'qrgentest', 'cat_20260205_094051_6983f52398757.jpg', '2026-02-04 17:40:51', '2026-02-04 17:40:51'),
(7, 'testing lang', 'cat_20260205_143039_6984390fb6579.png', '2026-02-04 22:30:39', '2026-02-04 22:30:39'),
(12, '1', 'cat_20260205_155715_69844d5b9c705.png', '2026-02-04 23:57:15', '2026-02-04 23:57:15'),
(13, '2', 'cat_20260205_155725_69844d650c535.png', '2026-02-04 23:57:25', '2026-02-04 23:57:25'),
(14, '3', 'cat_20260205_155738_69844d72a90fc.png', '2026-02-04 23:57:38', '2026-02-04 23:57:38'),
(16, '4', NULL, '2026-02-05 00:00:04', '2026-02-05 00:00:04'),
(17, '5', NULL, '2026-02-05 00:00:10', '2026-02-05 00:00:10'),
(18, '6', NULL, '2026-02-05 00:00:15', '2026-02-05 00:00:15'),
(19, '7', NULL, '2026-02-05 00:00:20', '2026-02-05 00:00:20'),
(20, '8', 'cat_20260212_101558_698d37de208cc.png', '2026-02-05 00:00:25', '2026-02-12 02:15:58'),
(21, 'Computer set 1', 'cat_20260210_090604_698a847c48f79.jpg', '2026-02-10 01:06:04', '2026-02-10 01:06:04'),
(22, 'Furnitures', NULL, '2026-02-13 02:29:45', '2026-02-13 02:29:45'),
(23, 'bulk_test_1', 'cat_20260216_160708_6992d02ca7246.png', '2026-02-16 08:07:08', '2026-02-16 08:07:08'),
(24, 'bulk_test_2', 'cat_20260216_160708_6992d02cb3022.png', '2026-02-16 08:07:08', '2026-02-16 08:07:08'),
(25, 'bulk_test_3', 'cat_20260216_160708_6992d02cbb95b.png', '2026-02-16 08:07:08', '2026-02-16 08:07:08'),
(26, 'PC set 2', NULL, '2026-02-19 06:20:52', '2026-02-19 06:20:52'),
(27, 'New UI test', 'cat_20260219_152205_6996ba1d85d38.png', '2026-02-19 07:22:05', '2026-02-19 07:22:05'),
(28, 'No Cat code', 'cat_20260219_161943_6996c79fb8941.png', '2026-02-19 08:19:43', '2026-02-19 08:19:43'),
(29, 'Asong call center', 'cat_20260223_080414_699b997e8fcea.png', '2026-02-23 00:04:14', '2026-02-23 00:04:14'),
(30, 'Asong nag ba bike', 'cat_20260223_080414_699b997e9aebf.png', '2026-02-23 00:04:14', '2026-02-23 00:04:14'),
(31, 'bulk 1', 'cat_20260226_112506_699fbd12a9a10.png', '2026-02-26 03:25:06', '2026-02-26 03:25:06'),
(32, 'bulk2', NULL, '2026-02-26 03:25:06', '2026-02-26 03:25:06'),
(33, 'bulk3', NULL, '2026-02-26 03:25:07', '2026-02-26 03:25:07'),
(34, 'Serialnotest', 'cat_20260226_170447_69a00caf91d21.png', '2026-02-26 09:04:47', '2026-02-26 09:04:47'),
(35, 'RevampedAddtest', 'cat_20260226_174032_69a01510bd5c5.png', '2026-02-26 09:40:32', '2026-02-26 09:40:32'),
(36, 'newpctest', 'cat_20260303_155936_69a694e873336.png', '2026-03-03 07:59:36', '2026-03-03 07:59:36'),
(37, 'actlogtest', NULL, '2026-03-04 01:55:49', '2026-03-04 01:55:49'),
(38, 'actlogtest1', NULL, '2026-03-04 01:56:45', '2026-03-04 01:56:45'),
(39, 'actlogtest2', NULL, '2026-03-04 02:08:47', '2026-03-04 02:08:47'),
(40, 'actlogtest3', NULL, '2026-03-04 02:13:46', '2026-03-04 02:13:46'),
(41, 'actlogtest4', NULL, '2026-03-05 02:30:46', '2026-03-05 02:30:46'),
(42, 'actlogtest5', NULL, '2026-03-05 02:30:46', '2026-03-05 02:30:46'),
(43, 'actlogdettest', NULL, '2026-03-05 02:36:34', '2026-03-05 02:36:34'),
(44, 'actlogdettest1', NULL, '2026-03-05 02:36:34', '2026-03-05 02:36:34'),
(45, 'removed avb qty', 'cat_20260309_161336_69ae81301b9f0.png', '2026-03-09 08:13:36', '2026-03-09 08:13:36');

-- --------------------------------------------------------

--
-- Table structure for table `csm_inventory`
--

CREATE TABLE `csm_inventory` (
  `inventory_id` int(11) NOT NULL,
  `inventory_system_item_code` varchar(100) NOT NULL,
  `item_description` text DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `item_cost` decimal(12,2) NOT NULL,
  `source_of_funds` varchar(150) DEFAULT NULL,
  `item_category_code` varchar(50) NOT NULL,
  `status` enum('available','currently used','out of stock','damaged','expired') NOT NULL DEFAULT 'available',
  `unit_quantity` int(11) NOT NULL COMMENT 'Original quantity received',
  `current_unit_quantity` int(11) NOT NULL COMMENT 'Remaining usable quantity',
  `unit_crit_level` int(11) NOT NULL COMMENT 'Critical stock threshold',
  `last_updated` date NOT NULL,
  `item_category_img` varchar(255) DEFAULT NULL,
  `qr_verification` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_image_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `csm_inventory`
--

INSERT INTO `csm_inventory` (`inventory_id`, `inventory_system_item_code`, `item_description`, `acquisition_date`, `item_cost`, `source_of_funds`, `item_category_code`, `status`, `unit_quantity`, `current_unit_quantity`, `unit_crit_level`, `last_updated`, `item_category_img`, `qr_verification`, `created_at`, `updated_at`, `category_image_id`) VALUES
(6, 'CSM-0002-0001', 'test', '2026-02-27', 22.00, 'test', 'CSM0002', 'available', 22, 23, 2, '2026-02-27', NULL, NULL, '2026-02-26 21:24:28', '2026-02-27 03:06:18', NULL),
(7, 'CSM-0001-0001', 'Example itemized description (full details/specs/notes)', '2026-02-27', 25.50, 'General Fund', 'CSM0001', 'available', 100, 80, 10, '2026-02-27', '8', NULL, '2026-02-26 21:52:49', '2026-02-27 04:01:18', NULL),
(8, 'CSM-0001-0003', 'Example itemized description (full details/specs/notes)', '2026-02-27', 25.50, 'test', 'CSM0001', 'available', 111, 22, 10, '2026-02-27', '7', NULL, '2026-02-26 22:00:54', '2026-02-27 04:01:15', NULL),
(9, 'CSM-0002-0004', 'test', '2026-02-27', 22.00, '', 'CSM0002', 'available', 28, 23, 2, '2026-02-27', NULL, NULL, '2026-02-26 22:00:54', '2026-02-26 22:04:28', NULL),
(10, 'CSM-0002-0005', 'test incr', '2026-02-27', 22.00, 'test', 'CSM0002', 'available', 22, 22, 2, '2026-02-27', NULL, NULL, '2026-02-27 03:06:44', '2026-02-27 03:06:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `csm_inventory_category`
--

CREATE TABLE `csm_inventory_category` (
  `category_id` int(11) NOT NULL,
  `item_category_name` varchar(150) NOT NULL,
  `category_image` varchar(255) DEFAULT NULL,
  `item_category_code` varchar(50) NOT NULL,
  `category_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `csm_inventory_category`
--

INSERT INTO `csm_inventory_category` (`category_id`, `item_category_name`, `category_image`, `item_category_code`, `category_photo`, `created_at`, `updated_at`) VALUES
(1, 'Office Supplies', NULL, 'CSM0001', 'cat_20260203_093756_ffc46e7579da.jpg', '2026-02-02 05:08:46', '2026-02-27 03:05:47'),
(2, 'Ballpoint Pen', NULL, 'CSM0002', NULL, '2026-02-02 06:00:58', '2026-02-27 03:05:51'),
(3, 'Pencils', NULL, 'CSM0003', 'cat_20260203_093014_150cd0d36aa6.jpg', '2026-02-03 00:58:28', '2026-02-27 03:05:55'),
(4, 'Ink Cartridge', NULL, 'CSM0004', NULL, '2026-02-03 02:52:31', '2026-02-27 03:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `csm_inventory_category_images`
--

CREATE TABLE `csm_inventory_category_images` (
  `image_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `csm_inventory_category_images`
--

INSERT INTO `csm_inventory_category_images` (`image_id`, `category_id`, `file_name`, `file_url`, `is_primary`, `created_at`) VALUES
(6, 1, 'cat_1_1772155137_0_IMG20260216151809.jpg', '', 1, '2026-02-27 01:18:57'),
(7, 1, 'cat_1_1772156511_0_IMG20260219143716.jpg', 'upload/category/cat_1_1772156511_0_IMG20260219143716.jpg', 0, '2026-02-27 01:41:51'),
(8, 1, 'cat_1_1772156514_0_IMG20260219122939.jpg', 'upload/category/cat_1_1772156514_0_IMG20260219122939.jpg', 0, '2026-02-27 01:41:54');

-- --------------------------------------------------------

--
-- Table structure for table `employment_status`
--

CREATE TABLE `employment_status` (
  `employment_status_id` int(11) NOT NULL,
  `status_code` varchar(50) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employment_status`
--

INSERT INTO `employment_status` (`employment_status_id`, `status_code`, `status_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Permanent', 'Permanent', 'Permanent Employment', '2026-01-30 06:24:37', '2026-01-30 06:24:37'),
(2, 'COS', 'Contract of Service', 'COS - Contractual Position', '2026-01-30 06:24:37', '2026-01-30 06:24:37'),
(3, 'JO', 'Job Order', 'JO - Job Order Position', '2026-01-30 06:24:37', '2026-01-30 06:24:37');

-- --------------------------------------------------------

--
-- Table structure for table `facility_records_assignments`
--

CREATE TABLE `facility_records_assignments` (
  `assignment_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `module_type` enum('AST','CSM','PERSONAL') NOT NULL,
  `source_item_id` int(11) DEFAULT NULL,
  `requisition_id` int(11) DEFAULT NULL,
  `item_code` varchar(120) NOT NULL,
  `item_description` text DEFAULT NULL,
  `qty` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) DEFAULT NULL,
  `issued_to_user_id` int(11) DEFAULT NULL,
  `accountable_user_id` int(11) DEFAULT NULL,
  `managed_by_user_id` int(11) DEFAULT NULL,
  `status` enum('ACTIVE','REPORTED','RETURN_REQUESTED','RETURNED','TRANSFERRED') NOT NULL DEFAULT 'ACTIVE',
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `returned_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_records_assignments`
--

INSERT INTO `facility_records_assignments` (`assignment_id`, `facility_id`, `unit_id`, `module_type`, `source_item_id`, `requisition_id`, `item_code`, `item_description`, `qty`, `unit`, `issued_to_user_id`, `accountable_user_id`, `managed_by_user_id`, `status`, `issued_at`, `returned_at`, `remarks`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'AST', 4, NULL, 'AST-0000-0001', 'asdfas', 1.00, 'pcs', NULL, NULL, NULL, 'REPORTED', '2026-03-09 09:44:42', NULL, 'okay', 1, 1, '2026-03-09 01:44:42', '2026-03-09 01:44:47'),
(2, 1, 1, 'AST', 50, 3, 'AST-1234-0004', '1234', 1.00, 'pcs', NULL, NULL, NULL, 'REPORTED', '2026-03-09 10:24:33', '2026-03-09 17:54:55', NULL, 1, 1, '2026-03-09 02:24:33', '2026-03-09 09:55:00'),
(3, 2, 2, 'AST', 55, NULL, 'AST-ASDFG-0001', 'asdgeqrgh', 1.00, 'pcs', NULL, NULL, 3, 'ACTIVE', '2026-03-09 16:21:05', NULL, NULL, 1, 1, '2026-03-09 08:21:05', '2026-03-09 08:21:05'),
(4, 2, 2, 'AST', 17, NULL, 'AST-0001-0004', 'Malaking monitor', 1.00, 'pcs', 3, 3, 3, 'ACTIVE', '2026-03-09 17:55:18', NULL, NULL, 1, 1, '2026-03-09 09:55:18', '2026-03-09 09:55:18'),
(5, 2, 3, 'AST', 41, NULL, 'AST-JIPRE-0003', 'Jipre Baluyot', 1.00, 'pcs', 11, 11, 3, 'ACTIVE', '2026-03-09 23:10:52', NULL, NULL, 1, 1, '2026-03-09 15:10:52', '2026-03-09 15:10:52');

-- --------------------------------------------------------

--
-- Table structure for table `facility_records_facilities`
--

CREATE TABLE `facility_records_facilities` (
  `facility_id` int(11) NOT NULL,
  `facility_code` varchar(50) NOT NULL,
  `facility_name` varchar(150) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_records_facilities`
--

INSERT INTO `facility_records_facilities` (`facility_id`, `facility_code`, `facility_name`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'AB', 'Admin Building', 1, 1, 1, '2026-03-09 01:37:08', '2026-03-09 01:39:03'),
(2, 'TEST', 'FOR TESTING', 1, 1, 1, '2026-03-09 03:03:29', '2026-03-09 03:03:29'),
(3, 'JMC 1', 'JMC Building 1st Floor', 1, 1, 1, '2026-03-09 05:58:41', '2026-03-09 05:58:41'),
(4, 'JMC 2', 'JMC Building 2nd Floor', 1, 1, 1, '2026-03-09 05:59:05', '2026-03-09 05:59:05'),
(5, 'JMC 3', 'JMC Building 3rd Floor', 1, 1, 1, '2026-03-09 05:59:20', '2026-03-09 05:59:20');

-- --------------------------------------------------------

--
-- Table structure for table `facility_records_history`
--

CREATE TABLE `facility_records_history` (
  `history_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `action` varchar(60) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_records_history`
--

INSERT INTO `facility_records_history` (`history_id`, `assignment_id`, `action`, `old_status`, `new_status`, `remarks`, `actor_user_id`, `created_at`) VALUES
(1, 1, 'ASSIGNED', NULL, 'ACTIVE', 'okay', 1, '2026-03-09 01:44:42'),
(2, 1, 'STATUS_UPDATE', 'ACTIVE', 'REPORTED', NULL, 1, '2026-03-09 01:44:47'),
(3, 2, 'CLAIMED_FROM_REQUISITION', NULL, 'ACTIVE', NULL, 1, '2026-03-09 02:24:33'),
(4, 2, 'STATUS_UPDATE', 'ACTIVE', 'REPORTED', NULL, 1, '2026-03-09 06:35:59'),
(5, 3, 'ASSIGNED', NULL, 'ACTIVE', NULL, 1, '2026-03-09 08:21:05'),
(6, 2, 'STATUS_UPDATE', 'REPORTED', 'RETURNED', NULL, 1, '2026-03-09 09:54:55'),
(7, 2, 'STATUS_UPDATE', 'RETURNED', 'REPORTED', NULL, 1, '2026-03-09 09:55:00'),
(8, 4, 'ASSIGNED', NULL, 'ACTIVE', NULL, 1, '2026-03-09 09:55:18'),
(9, 5, 'ASSIGNED', NULL, 'ACTIVE', NULL, 1, '2026-03-09 15:10:52');

-- --------------------------------------------------------

--
-- Table structure for table `facility_records_units`
--

CREATE TABLE `facility_records_units` (
  `unit_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `unit_type` enum('ROOM','OFFICE','LABORATORY','OTHER') NOT NULL DEFAULT 'ROOM',
  `unit_code` varchar(50) NOT NULL,
  `unit_name` varchar(150) NOT NULL,
  `facility_unit_manager_user_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_records_units`
--

INSERT INTO `facility_records_units` (`unit_id`, `facility_id`, `unit_type`, `unit_code`, `unit_name`, `facility_unit_manager_user_id`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'OFFICE', 'MISD', 'Management Information System Department', 3, 1, 1, 1, '2026-03-09 01:39:40', '2026-03-09 07:18:55'),
(2, 2, 'OFFICE', 'TEST', 'test', 3, 1, 1, 1, '2026-03-09 03:11:07', '2026-03-09 03:11:07'),
(3, 2, 'ROOM', 'TEST 2', 'test 2', 3, 1, 1, 1, '2026-03-09 12:52:56', '2026-03-09 12:52:56');

-- --------------------------------------------------------

--
-- Table structure for table `requisition_items`
--

CREATE TABLE `requisition_items` (
  `requisition_id` int(11) NOT NULL,
  `module_type` enum('AST','CSM') NOT NULL,
  `item_code` varchar(100) NOT NULL,
  `item_description` text DEFAULT NULL,
  `qty_requested` int(11) NOT NULL DEFAULT 1,
  `requester_user_id` int(11) NOT NULL,
  `status` enum('pending','reviewed','approved','disapproved') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `approved_by_user_id` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `claimed_by_user_id` int(11) DEFAULT NULL,
  `claimed_at` datetime DEFAULT NULL,
  `claim_assignment_id` int(11) DEFAULT NULL,
  `claim_facility_id` int(11) DEFAULT NULL,
  `claim_unit_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisition_items`
--

INSERT INTO `requisition_items` (`requisition_id`, `module_type`, `item_code`, `item_description`, `qty_requested`, `requester_user_id`, `status`, `reason`, `approved_by_user_id`, `approved_at`, `claimed_by_user_id`, `claimed_at`, `claim_assignment_id`, `claim_facility_id`, `claim_unit_id`, `created_at`, `updated_at`) VALUES
(1, 'AST', 'AST-0000-0011', 'sfgsdfg', 1, 5, 'disapproved', 'ayoko', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 07:21:04', '2026-03-05 09:25:04'),
(2, 'AST', 'AST-1234-0005', '1234', 1, 3, 'approved', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-09 01:43:21', '2026-03-09 01:43:43'),
(3, 'AST', 'AST-1234-0004', '1234', 1, 3, 'approved', NULL, 1, '2026-03-09 10:24:06', 1, '2026-03-09 10:24:34', 2, 1, 1, '2026-03-09 02:23:16', '2026-03-09 02:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `general_id` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `f_name` varchar(255) NOT NULL,
  `m_name` varchar(255) NOT NULL,
  `l_name` varchar(255) NOT NULL,
  `suffix` varchar(255) NOT NULL,
  `sex` varchar(100) NOT NULL,
  `birth_date` varchar(255) NOT NULL,
  `user_role` text DEFAULT '[""]' COMMENT '''1'' => ''ADMIN'', ''2'' => ''REGISTRAR'', ''3'' => ''VPAA'', ''4'' => ''OFFICIAL'', ''5'' => ''FACULTY'', ''6'' => ''STUDENT''',
  `username` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email_address` varchar(255) NOT NULL DEFAULT '',
  `recovery_email` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `employment_status_id` int(11) DEFAULT NULL,
  `assigned_access` varchar(100) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `locked` int(11) NOT NULL,
  `last_signin` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `general_id`, `img`, `f_name`, `m_name`, `l_name`, `suffix`, `sex`, `birth_date`, `user_role`, `username`, `password`, `email_address`, `recovery_email`, `position`, `employment_status_id`, `assigned_access`, `status`, `locked`, `last_signin`) VALUES
(1, 'CGC-08626', '', 'Marlon', 'Llanes', 'Reolo', '', 'male', '2000-11-04', '[\"1\",\"2\"]', 'mlreolo@ccc.edu.ph', '779a8d6c3c12a58398e76f3146ae3874454e97c5', 'mlreolo@ccc.edu.ph', 'mlreolo@ccc.edu.ph', 'Administrative', 1, 'DTE', 0, 0, '0000-00-00 00:00:00'),
(2, 'po_as', '', 'PO', 'PO', 'PO', '', 'male', '2011-03-04', '[\"3\"]', 'po_as', '6380d71637c7aa7cc3b34474d08e97b139ad0d32', 'asdfasdfasdf@gmail.com', '', 'Administrative', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(3, 'user', '', 'USER', 'USER', 'USER', '', 'male', '2011-03-05', '[\"4\"]', 'user', '9842d454dfa08a3a957c56bdbe5bc01746e21424', 'user@gmail.com', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(4, '2026-0001', '', 'ANNA', 'MARIA', 'REYES', '', 'female', '1980-01-15', '[\"4\"]', 'teachuser1', 'passwrd1', 'anna.reyes@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(5, '2026-0002', '', 'JUAN', 'CRUZ', 'SANTOS', '', 'male', '1978-03-22', '[\"4\"]', 'teachuser2', 'passwrd2', 'juan.santos@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(6, '2026-0003', '', 'EMILY', 'GRACE', 'LOPEZ', '', 'female', '1985-07-09', '[\"4\"]', 'teachuser3', 'passwrd3', 'emily.lopez@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(7, '2026-0004', '', 'MICHAEL', 'JAMES', 'GARCIA', '', 'male', '1975-11-30', '[\"4\"]', 'teachuser4', 'passwrd4', 'michael.garcia@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(8, '2026-0005', '', 'SOPHIA', 'ANNE', 'RAMOS', '', 'female', '1990-05-18', '[\"4\"]', 'teachuser5', 'passwrd5', 'sophia.ramos@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(9, '2026-0006', '', 'DAVID', 'LEE', 'MENDOZA', '', 'male', '1982-09-25', '[\"4\"]', 'teachuser6', 'passwrd6', 'david.mendoza@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(10, '2026-0007', '', 'ISABELLA', 'MARIE', 'CASTRO', '', 'female', '1988-12-12', '[\"4\"]', 'teachuser7', 'passwrd7', 'isabella.castro@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(11, '2026-0008', '', 'MARK', 'ANTHONY', 'DELOS SANTOS', '', 'male', '1979-04-03', '[\"4\"]', 'teachuser8', 'passwrd8', 'mark.delossantos@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(12, '2026-0009', '', 'PATRICIA', 'JOY', 'NAVARRO', '', 'female', '1983-08-27', '[\"4\"]', 'teachuser9', 'passwrd9', 'patricia.navarro@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00'),
(13, '2026-0010', '', 'ROBERT', 'JOHN', 'LIM', '', 'male', '1977-02-14', '[\"4\"]', 'teachuser10', 'passwrdA', 'robert.lim@ccc.edu.ph', '', 'Academic Personnel', 1, NULL, 0, 0, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_access`
--

CREATE TABLE `user_access` (
  `user_access_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `access_code` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_access`
--

INSERT INTO `user_access` (`user_access_id`, `user_id`, `access_code`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 3, 'AST', 1, '2026-02-09 08:02:48', '2026-02-09 08:02:48'),
(7, 4, 'CSM', 1, '2026-02-23 01:12:55', '2026-02-23 01:12:55'),
(16, 2, 'PO', 1, '2026-03-09 03:42:20', '2026-03-09 03:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `user_log`
--

CREATE TABLE `user_log` (
  `user_log_id` int(11) NOT NULL,
  `login_date` datetime NOT NULL,
  `logout_date` datetime NOT NULL,
  `action` varchar(20) NOT NULL,
  `user_id` text NOT NULL,
  `session_id` text NOT NULL,
  `ip_address` varchar(20) NOT NULL,
  `device` varchar(255) NOT NULL,
  `system_id` int(11) NOT NULL DEFAULT 0,
  `token_id` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`token_id`)),
  `login_flag` int(11) NOT NULL DEFAULT 0,
  `user_level` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_log`
--

INSERT INTO `user_log` (`user_log_id`, `login_date`, `logout_date`, `action`, `user_id`, `session_id`, `ip_address`, `device`, `system_id`, `token_id`, `login_flag`, `user_level`) VALUES
(1, '2025-09-08 12:43:20', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2025-09-08 12:43:20\", \"LOGIN\", \"::1\"], [\"2025-09-08 13:02:22\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"139.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 139.0.0.0 on Windows 10 64-bit\"}', 0, '[\"b1823a1ba1c6a33da356437e44036ff816ffdda681481153c23afebeabae3478\", \"1f48e1aa5310c52ec704243ffee61685edcbbe4ddc1b6999fcad60ed6191ca2f\"]', 1, 1),
(2, '2025-10-08 08:06:47', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2025-10-08 08:06:47\", \"LOGIN\", \"::1\"], [\"2025-10-08 10:02:38\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"141.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 141.0.0.0 on Windows 10 64-bit\"}', 0, '[\"b46f4635d8c14aa347a67499b0caaba4a05c18e8cf4f3bdc06c9afdfdf4d0519\", \"aad429b843a06ec47cfd45fa3945f79db9b5f2e6c74b0bdc854e41d4d4a5132a\"]', 1, 1),
(3, '2026-01-21 12:27:26', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-21 12:27:26\", \"LOGIN\", \"::1\"], [\"2026-01-21 12:27:31\", \"LOGIN\", \"::1\"], [\"2026-01-21 12:28:02\", \"LOGIN\", \"::1\"], [\"2026-01-21 12:58:33\", \"LOGIN\", \"::1\"], [\"2026-01-21 13:02:35\", \"LOGIN\", \"::1\"], [\"2026-01-21 13:38:28\", \"LOGIN\", \"::1\"], [\"2026-01-21 14:01:26\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"b0e458e8cc33efbf5976d6ebf66261b924b1d8450e5c3e82fce030b8e84de7fc\", \"a38e6378ea47252a68680f8e7d1164a01fee25408df2a692c87179c1f1967b3f\", \"4281c59c17500367336ae40f7d65784bb970dd4b00aa31fd784a72ddcb61751b\", \"b505e05b11abbde8dbaf646fd670fe59029adc635d60d248e545a203091f636d\", \"90cd88b5747c347d277bbf8acd8a725cfcf5e0095db271875ef37d9795af4cc6\", \"7c98e22fb9cb1cd7e65185455e8e80635ce24a9aa8d5039113acae479f382ca4\", \"8bfab2c5f850368d9d3b02a8f4f3c215841c7b55789cf579e45f80b6353f5be1\"]', 1, 2),
(4, '2026-01-21 12:35:06', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-21 12:35:06\", \"LOGIN\", \"::1\"], [\"2026-01-21 13:01:03\", \"LOGIN\", \"::1\"], [\"2026-01-21 13:01:15\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"adef991adc655d3124c02bb7d509a6d7f9211bda8f64a73a7165014320e0cee4\", \"72589bc4a90a4b9d7433ede876884ed7efce42205d94d73343d5892f0f172715\", \"54ff9ad398265a634adbe0565ec6ec0ded91cc88bc98ba274e10adeeaf5a3a30\"]', 1, 2),
(5, '2026-01-22 06:52:39', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-22 06:52:39\", \"LOGIN\", \"::1\"], [\"2026-01-22 09:14:15\", \"LOGIN\", \"::1\"], [\"2026-01-22 09:22:33\", \"LOGIN\", \"::1\"], [\"2026-01-22 09:22:47\", \"LOGIN\", \"::1\"], [\"2026-01-22 12:23:36\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"e2c633e2f257e8ae1866cd0d225fa6046da7386cf24a2851decd9fad91194ca8\", \"286b5d7c3526f68f3813f2ce184ad917b05f8f9e9e0f477f3c7525cee94fb9e1\", \"b013eb1e94055c1880e10190d228df78336103030cfcaf252538db94a8db464a\", \"d327de52004da4b6397785f36a8885a9f1d3cb5f70f666658083fca27087ffea\", \"ccac4f09538565bafb04407e7fe79bc56bebcf0dc060d59fc7a80a5fd174a365\"]', 1, 2),
(6, '2026-01-22 12:28:07', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-22 12:28:07\", \"LOGIN\", \"::1\"], [\"2026-01-22 12:28:15\", \"LOGIN\", \"::1\"], [\"2026-01-22 12:28:42\", \"LOGIN\", \"::1\"], [\"2026-01-22 12:30:39\", \"LOGIN\", \"::1\"], [\"2026-01-22 12:33:35\", \"LOGIN\", \"::1\"], [\"2026-01-22 12:33:43\", \"LOGIN\", \"::1\"], [\"2026-01-22 12:38:10\", \"LOGIN\", \"::1\"], [\"2026-01-22 13:04:21\", \"LOGIN\", \"::1\"], [\"2026-01-22 13:05:31\", \"LOGIN\", \"::1\"], [\"2026-01-22 13:05:37\", \"LOGIN\", \"::1\"], [\"2026-01-22 13:07:27\", \"LOGIN\", \"::1\"], [\"2026-01-22 13:31:32\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"a49ac887999edc448d96e247302cc76b68daa7d1690456d45561c10fe9b9866a\", \"75221caacafbeff76fd31e986fa8dcc4a3a9c4bfc9c98fa41b9fe7ebab4b003b\", \"9a1d5fe461e77c755a9a4e54e661cf314b1abf235c4330c78882886be5eb75c7\", \"53812ad9be565f3419e4fb55bfe2d05db86586224de932da0f00fb61035f426f\", \"75c9e041ca94d94a4f3cd74ba213040d2e935bf0d2e35c61cd7ad702c75a251d\", \"0a15498a4cdd3be2d45c35e937c1f9e865b601d2593e8aaf397dc19247c4e3c7\", \"5a95b9b2a0605ea77c7f3d7fecf03778af6723d14eeac5ce6e58d90f27487b13\", \"1bd843d22111913733a4faeedec1ace9b8ff9cebdd7992f099d8de6c5cbfe835\", \"13df54c68508f639ddd64075cc03322ca577ab33a5812a7402149bb7e6247228\", \"4a51f048dbfbc52ebfaa268681841e85a049f54b378551c5f9a4d6ba9a37ef39\", \"5f59678f874c287c5c08e3a9c7d77a43331c8f110a8aa488cd3bcb056ecb4f01\", \"d26d7f398a1f8114c6c84997140277d80972ca5788c038ddc4d240e5122b9512\"]', 1, 2),
(7, '2026-01-23 06:52:53', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-23 06:52:53\", \"LOGIN\", \"::1\"], [\"2026-01-23 06:55:52\", \"LOGIN\", \"::1\"], [\"2026-01-23 07:02:04\", \"LOGIN\", \"::1\"], [\"2026-01-23 07:24:50\", \"LOGIN\", \"::1\"], [\"2026-01-23 07:25:08\", \"LOGIN\", \"::1\"], [\"2026-01-23 07:25:41\", \"LOGIN\", \"::1\"], [\"2026-01-23 07:28:51\", \"LOGIN\", \"::1\"], [\"2026-01-23 08:33:59\", \"LOGIN\", \"::1\"], [\"2026-01-23 09:52:01\", \"LOGIN\", \"::1\"], [\"2026-01-23 15:22:03\", \"LOGIN\", \"::1\"], [\"2026-01-23 15:24:05\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"5c7959642bfb8f466ec88206450faa40526a49d0fbc4720a31017799e2528c9c\", \"a5453e022d93af723b93cb2aa9b664af113f1e7d7efe0580422cca9c310a7e25\", \"a299f31f53b9f83493ba528b3a1bf0a9d685357f6daede9c68c6a7fe12697065\", \"faca82000c53f5d5fb549efdbcd7ba63f9ff9daa4698dc5ef194568b9490e7cb\", \"0882080f33af9aa230f7a5cc645e8b926c7c578fe8ba1a0d72ee69bdccf74f72\", \"73c546f7b354fcff7c8e3c1c42b9d6dc0cb2343a65d19c4059969e17188d4809\", \"b5bf650ba32cff9093a9220b41fd18c07f3ec7cd2f5a83e91564aa39c1255623\", \"b625d330bfcd0fa47e324341444106f8cd3423d36b209bd01e095c431484e54d\", \"9f83c88982085c5841bcd8e4ad19730cc8822e4c12aa90bcd82fe1bfca71320f\", \"767595a7ae68d103aef2c20ab7a2d9d535c8daf795280d091f2b7d39f6f2ddb7\", \"c33890b294968adcd4840a1c45edfaa953759d3910b216c450cce952ff99046c\"]', 1, 2),
(8, '2026-01-26 06:38:54', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-26 06:38:54\", \"LOGIN\", \"::1\"], [\"2026-01-26 06:47:10\", \"LOGIN\", \"::1\"], [\"2026-01-26 06:53:20\", \"LOGIN\", \"::1\"], [\"2026-01-26 07:22:34\", \"LOGIN\", \"::1\"], [\"2026-01-26 07:22:44\", \"LOGIN\", \"::1\"], [\"2026-01-26 08:47:40\", \"LOGIN\", \"::1\"], [\"2026-01-26 09:18:17\", \"LOGIN\", \"::1\"], [\"2026-01-26 09:18:29\", \"LOGIN\", \"::1\"], [\"2026-01-26 09:49:49\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"723226c4e8462ed0e4e5c5a8dc691b024752cc22bdb8615261652a936141c15a\", \"2426355dc9335a40acefd2b8649593728179b7df78160c9fbf925d5947b6cf97\", \"06c3250e9617ac7088a92481ba328029d9a5cac57b0506dacecd3906f444a6a2\", \"5928b7b6575505afecb8027df36cf62395dbcf500f6d2b0dd1ec8f1ee39adace\", \"0675666b09fcff6ff161460ef05954bc03308f7a7615d0dcfdb7f99b5d461175\", \"d18f8164dbd6f12740f68d0d77584f800aca3c63c5a7283d05d4cc6d38a615ee\", \"6a2ed76f63dfd2baf4679f845964c516fef09f87bea1bf430b3c235aec22529f\", \"ab5a08fb000ebc284ed23f129746fce90ffd9075a890f24558dcd615f15f5967\", \"d0faf05a5678aea79233d8522e52fdfb73cd8d46b5db8b1e410a4c09721e6683\"]', 1, 2),
(9, '2026-01-26 08:46:05', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-26 08:46:05\", \"LOGIN\", \"::1\"], [\"2026-01-26 08:50:41\", \"LOGIN\", \"::1\"], [\"2026-01-26 08:52:49\", \"LOGIN\", \"::1\"], [\"2026-01-26 09:03:39\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"9b2ea5082893821135f3b98ff64d7d8b3a175c7a19432981779b17723ca9c5b9\", \"efec28579409c8c11ba7baf33b5ab1e56872996e4bd97452cf628db3322636cc\", \"13b7e2c8ee494ea1c3b74282be22f98ae2f1fc8818d23d39a2998b29ae37fe91\", \"19d16eaf3178b1fd6442256b5af7ad553f38f481230cf3e6519721663d6b98f6\"]', 1, 2),
(10, '2026-01-26 09:25:44', '0000-00-00 00:00:00', 'LOGIN', '3', '[[\"2026-01-26 09:25:44\", \"LOGIN\", \"::1\"], [\"2026-01-26 09:28:46\", \"LOGIN\", \"::1\"], [\"2026-01-26 09:29:14\", \"LOGIN\", \"::1\"], [\"2026-01-26 09:45:56\", \"LOGIN\", \"::1\"], [\"2026-01-26 09:50:33\", \"LOGIN\", \"::1\"], [\"2026-01-26 12:37:25\", \"LOGIN\", \"::1\"], [\"2026-01-26 12:37:38\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"97758e5cca7b0fbda2a00772e7d2f4785cf5effbe908865c1013b83648f37ed0\", \"d5221427cf16a675845b47a1d05d2f15707cfc9e7f2c1226280057467378b8bb\", \"a261f825b29dc902d4f4a5f94ab69e608c2d75604f25cc7a3795e9d628522cf0\", \"56dde131cb66463f993d9e0cbb257e95acd8d11c906994860e36f8ca51684e20\", \"76bbcac2b9b1ec173c0df6b20d9ce1c4aa4883db73d89aa580fbd7ac388e4f81\", \"e6f609989be4513b70ff0751beb738132483b75d84e7ad879b0fa608f89e422f\", \"dd19eb5789fb59d4bcfc66ecf3ac05ed633b2748eb4a630d9fde9805e0e8f840\"]', 1, 1),
(11, '2026-01-27 08:25:58', '0000-00-00 00:00:00', 'LOGIN', '3', '[[\"2026-01-27 08:25:58\", \"LOGIN\", \"::1\"], [\"2026-01-27 08:39:28\", \"LOGIN\", \"::1\"], [\"2026-01-27 08:58:28\", \"LOGIN\", \"::1\"], [\"2026-01-27 09:15:47\", \"LOGIN\", \"::1\"], [\"2026-01-27 09:16:08\", \"LOGIN\", \"::1\"], [\"2026-01-27 09:17:09\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"b07f0f0c44c2d3c4f8ee4ffd5e6244d5e8be9aea94a01976370b19d9acbcfd08\", \"653b963ea74460ee23bd1566ad8ec88865a5206262ffd6fc527556cdacc2a5ff\", \"69b2cf3d6f8956237641c28caab6a19a8879f4957ba5964f71493190661fac4c\", \"6c7b7f3a657f39cb4cefb9df7c1939195eaaa5ad9942839fbe6329c23213d451\", \"935e4406df241d03ee191c9f5ab3fb057309ec07014dcf389888863ba056988c\", \"1420314478ed233b492090fe80609512ecacf01259d7aa088645496f7f175f5b\"]', 1, 1),
(12, '2026-01-27 15:06:16', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-27 15:06:16\", \"LOGIN\", \"::1\"], [\"2026-01-27 15:08:28\", \"LOGIN\", \"::1\"], [\"2026-01-27 15:08:48\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"ebdee4a3d332cf513a6f18e41c1d3e16d98fa84a4b3f9fc09ccdf7959828d5cd\", \"e30983cff791a6ec66f606d44c1a3c231e8e2bb293ff1939bdd4d939672bae18\", \"42c592ea95304808f82601f781581ba8e312c69534b2eccbcbe35db7f5fe1710\"]', 1, 1),
(13, '2026-01-27 15:10:45', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-27 15:10:45\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"259d2bf43a707887091908f8a3808424aa6f63eef2ea3c15622ec794702b03f0\"]', 1, 2),
(14, '2026-01-28 07:21:03', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-28 07:21:03\", \"LOGIN\", \"::1\"], [\"2026-01-28 07:24:08\", \"LOGIN\", \"::1\"], [\"2026-01-28 08:45:13\", \"LOGIN\", \"::1\"], [\"2026-01-28 13:51:23\", \"LOGIN\", \"::1\"], [\"2026-01-28 15:08:56\", \"LOGIN\", \"::1\"], [\"2026-01-28 15:09:07\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"5b49d51d6716d17653831db46dd6db99b3cb76cbf7722cbde52926cbeba40e57\", \"265d6ae21693052e86f34349d841ea4634e3fbfb50f7bc868fd2180620333d1e\", \"28273ba2c1a32cf07dc390d8bba740f866193fcfdf31da18d2579267c22da000\", \"e335ff8d0fee00b10f141ddb50303f6229a2be57cf3b5453a4cb3795813ca4de\", \"d83ccd13af52cb9be7c5cc11a04c42dc19f6f51950c2497e5637ac9af1022cbe\", \"336f83486deedf47472e1561052f8c39e3127d99c5e85b11ada7186a533349a1\"]', 1, 1),
(15, '2026-01-28 08:29:42', '0000-00-00 00:00:00', 'LOGIN', '3', '[[\"2026-01-28 08:29:42\", \"LOGIN\", \"::1\"], [\"2026-01-28 08:29:54\", \"LOGIN\", \"::1\"], [\"2026-01-28 08:43:35\", \"LOGIN\", \"::1\"], [\"2026-01-28 09:15:51\", \"LOGIN\", \"::1\"], [\"2026-01-28 10:23:30\", \"LOGIN\", \"::1\"], [\"2026-01-28 13:49:58\", \"LOGIN\", \"::1\"], [\"2026-01-28 13:51:44\", \"LOGIN\", \"::1\"], [\"2026-01-28 14:00:39\", \"LOGIN\", \"::1\"], [\"2026-01-28 15:04:17\", \"LOGIN\", \"::1\"], [\"2026-01-28 15:07:35\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"c04848b1b1bdf9fb3d0ca72a51a02c7f2ac9767727c13b6481ed5d64c88d7dc8\", \"a66496f52dc50349e38116c9c44a826c55c1e9ab71c8a8bcb1789d9e606cf985\", \"f789c03e1e516cc0a60b45947bcef024f24b4be82572df667448cd92677be49c\", \"25143f8113e0fd129a5d68abdbac2b82abc1ee9fddd80d0f91ada7d34b07b312\", \"972e32930be8753806e73b09c82da3f398e918a467c48e3befd667db1620d9e7\", \"88286b609dfe2fba9c62bb3832a6d36a64d471fb7f47bf1864b28726383fe715\", \"9b2684b27123587accf727d407cd5039a853cf64ac60e3ee6fc104985ece9c00\", \"e4042f61a356ecc4a6a912a07038c52a272fb6c982543edc13426a7ee7bb2a76\", \"d2ac215a02f140b399f8960c5ea92a7a9cf504442bb43c324b3598c7d909c0da\", \"c4100b39141868303d2c7c2542e5967b45d21b087d4f3d5a85175c735cfb041b\"]', 1, 1),
(16, '2026-01-28 15:08:11', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-28 15:08:11\", \"LOGIN\", \"::1\"], [\"2026-01-28 15:10:42\", \"LOGIN\", \"::1\"], [\"2026-01-28 15:23:19\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"f35ee8c93543b74a025d5ff1a0126035bc032b0ca67dbcd3d720bac0c76c42cb\", \"0d3f1d5f0a2b7811b6530b8d2da3cf29e2eac2fe29fc73a2c5247aa5a11a9f6d\", \"c3f667a51ade39c6dceae737fb5d7f65d082210a05a194992fbf8085100def51\"]', 1, 1),
(17, '2026-01-29 08:29:20', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-29 08:29:20\", \"LOGIN\", \"::1\"], [\"2026-01-29 08:52:07\", \"LOGIN\", \"::1\"], [\"2026-01-29 12:12:36\", \"LOGIN\", \"::1\"], [\"2026-01-29 15:25:27\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"9cb025e00b4ed4c51c84d161c79b9f5fa7953fc9c8780c0289a600f10616e204\", \"e3e29837afb970ce9be65ae005bf703f95490441591e161a3e323d718b10c36c\", \"ceaa75de61beaadcd4a1c4de0dfbb558b845daa1bc0d742d814a432855165d4a\", \"091f0fe6767da96d1dced62bdf15543b4a50ca92a74f299b255172397542bfd5\"]', 1, 1),
(18, '2026-01-30 07:17:19', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-30 07:17:19\", \"LOGIN\", \"::1\"], [\"2026-01-30 09:22:02\", \"LOGIN\", \"::1\"], [\"2026-01-30 11:51:51\", \"LOGIN\", \"::1\"], [\"2026-01-30 11:52:56\", \"LOGIN\", \"::1\"], [\"2026-01-30 12:07:17\", \"LOGIN\", \"::1\"], [\"2026-01-30 12:08:51\", \"LOGIN\", \"::1\"], [\"2026-01-30 14:01:52\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"4a6035ea13ec10c29c62d831fb4f0e317f3efade3908eae58a0d4bb0576d38b6\", \"b0bb12c4302d32d8a4169829cd4db4d4e0042d4ca792a4afe6ce88d862327650\", \"0b17afc136dfb561313a5241afdb4cc52537cc2157cad2964924b8dde22da8f4\", \"8cb5464f073cf75e6c79ee06181fccfd2c320c9c28eb57f1cd31055f63c22a94\", \"29734f37bce74a0e85ea59f20f1fdc948d81d098e64b219266aa15dd10e0197f\", \"d46b985f01855c0741fb983b58b90902dbda9f34679a1c115f5187672ba77f56\", \"af354beee0ba24e67b7165e3c6a032ab449ff767f997bc24281d41f694051316\"]', 1, 2),
(19, '2026-01-30 12:16:32', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-30 12:16:32\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"3bf1a9520acb46dae9b452699d2b1aacaf6b3383938d8aab000fc65206edfc6d\"]', 1, 1),
(20, '2026-02-02 05:37:43', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-02 05:37:43\", \"LOGIN\", \"::1\"], [\"2026-02-02 07:19:23\", \"LOGIN\", \"::1\"], [\"2026-02-02 07:32:57\", \"LOGIN\", \"::1\"], [\"2026-02-02 08:16:36\", \"LOGIN\", \"::1\"], [\"2026-02-02 08:55:53\", \"LOGIN\", \"::1\"], [\"2026-02-02 08:56:13\", \"LOGIN\", \"::1\"], [\"2026-02-02 09:03:45\", \"LOGIN\", \"::1\"], [\"2026-02-02 09:07:53\", \"LOGIN\", \"::1\"], [\"2026-02-02 09:11:43\", \"LOGIN\", \"::1\"], [\"2026-02-02 11:56:27\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"623cfc1fde505bd0eb1ce93e3e6dfc4d6b70b8427adbdbe171110f9eac87b5e4\", \"f8ed42312bc8cca0f827e05e077c0078b50e43016b79d8399c7c7c05b8786c85\", \"8d59e8b7623a891fdb296721b7cdb0e7b09bfc6f4c00dae77700f8c04ebc0f30\", \"7cb71e354eecfd7b977bc00706103b780cc8ca774045cf654c10fcfc0814c017\", \"4f30bd3c5efa2f559866e16889ff0607f2d4edfe88c9498aa744a579cb98bbee\", \"362ac32526d462529c7cfa9928d4c49c03bd38c78396ffa80c7d29389afcb7db\", \"ab0bfe5ab410cb188f0e9df164cf8eef07fee3d9e9eccc560512408ef43d72c9\", \"deff1ef3bc60c622c35b609aea98dc8e60fbecf9020eaaf5a513215837c69a43\", \"20d150486246be7db7fcbb85ac9e647b9a39fe6ef38488f55a5e0ae7ff17bc41\", \"6d0e103b6f4d1b0b4ee9c6da250a4274e487c69a4c3eff956209faf4b032e193\"]', 1, 1),
(21, '2026-02-02 09:10:54', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-02 09:10:54\", \"LOGIN\", \"::1\"], [\"2026-02-02 12:32:16\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"2cbdefccf58a97aed0f847997c3abe6f5c6a0aa46f91bf1d18781f5735e3ab95\", \"d764da0686ba3e3a9e83508573ed78c4980f1f4523541cbba552fbddc08baf6d\"]', 1, 2),
(22, '2026-02-03 06:47:16', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-03 06:47:16\", \"LOGIN\", \"::1\"], [\"2026-02-03 07:34:09\", \"LOGIN\", \"::1\"], [\"2026-02-03 07:34:21\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"fe9e4acd0548a0554431b91720128b3eb8492cf7f13c81d21ae67c8e0e178a4e\", \"9d0b11e2c9435f5c6c41333dc7b4f6ea74c109b1b9bcf853e5b42a910120149f\", \"c916cadbfb7c9c044e363162c164d3341f7c72c8ee24c13408f8552b71703105\"]', 1, 1),
(23, '2026-02-03 15:08:52', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-03 15:08:52\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"211bb291d6efd40b1c39063f608618ba398bedff07ba15e1be3c8597e62f8b70\"]', 1, 2),
(24, '2026-02-04 06:57:55', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-04 06:57:55\", \"LOGIN\", \"::1\"], [\"2026-02-04 06:58:16\", \"LOGIN\", \"::1\"], [\"2026-02-04 06:58:46\", \"LOGIN\", \"::1\"], [\"2026-02-04 09:42:21\", \"LOGIN\", \"::1\"], [\"2026-02-04 09:42:54\", \"LOGIN\", \"::1\"], [\"2026-02-04 09:45:29\", \"LOGIN\", \"::1\"], [\"2026-02-04 09:45:50\", \"LOGIN\", \"::1\"], [\"2026-02-04 10:05:28\", \"LOGIN\", \"::1\"], [\"2026-02-04 10:05:36\", \"LOGIN\", \"::1\"], [\"2026-02-04 10:23:09\", \"LOGIN\", \"::1\"], [\"2026-02-04 11:53:44\", \"LOGIN\", \"::1\"], [\"2026-02-04 12:34:49\", \"LOGIN\", \"::1\"], [\"2026-02-04 14:03:09\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"33ea1bf317704ba3860014191e0f53ceba5834edc0139101d6acbac0e6251cb6\", \"579649e7a924efd4eca4dc8430e19176a295423e94c97c808d2285b2a3035c5c\", \"d52119ee1afc1c480a0bbaca6f8b7e45ff723030548f4dca4790419b34d873ee\", \"17aec89c5b9aa0f9bbf886843fbd9f5e6abb9e3e04bf0f9664558c9ec183f651\", \"57ea369560204884d27ec79352cc93a019eb550f605b685ad39010960fd6e72f\", \"5613c177d505a9f9ddd1b957d3ce049cef5f9ef08bee0c624810eecf9a16aa43\", \"0bbafe5c549bf259d6c36d0c2889f615da8841244517efab5a2e1a8ae96c976f\", \"4aa18cfeeff4c3f4e40bccea5f38b003d70dc8dec6a71be110708af9eb5a9401\", \"6faea8d68a5ba1d00de508ed320d2494c177c6d0f679cfb9f81f88afc4e3ec8b\", \"7a5cb1d7b0a7e46fda39d1c400b74690adc716f15b2588b2935b3c4e1a479289\", \"417d17e17f8cb206e79d14fee5237879dfc32d7509be6788e0e7a734dcb8184f\", \"fb26cb9683c0259d8f4ad6c61fab8ab39212795053ef42824304fa1e4c78e9c2\", \"e1c9484ba5bee4720e9a4641ac64189efccf6f96047774b2f8415b170fb9022f\"]', 1, 1),
(25, '2026-02-04 08:21:45', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-04 08:21:45\", \"LOGIN\", \"::1\"], [\"2026-02-04 08:30:56\", \"LOGIN\", \"::1\"], [\"2026-02-04 09:25:06\", \"LOGIN\", \"::1\"], [\"2026-02-04 12:39:08\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"f8a1a9edbb68af9e60432d17cd4afbaee088ffb42f11af6099fb51dd81e8f20e\", \"67f83070987ca0b0b435e4515c1c3aafa94555b76cef6ff2f8a1653a372e1f2a\", \"5490b822c3757ac46c2f8d4a2f772984d3af098fac72e789b723003114b9a1d7\", \"9490c87a9bc9523a8e41e7818fa1c6dcb3fa6fac5556ad1fd71dc860c011e408\"]', 1, 2),
(26, '2026-02-05 08:16:20', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-05 08:16:20\", \"LOGIN\", \"::1\"], [\"2026-02-05 14:30:12\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"1a6e8c0e7aaaf4b020906a1a05a9cf5716d8380881d2a5605b1482be02cfa35f\", \"82f7cc6423b7be11e795d0cd1c549166e28b9f299b9f792de3baaee1f3e3857a\"]', 1, 1),
(27, '2026-02-09 07:22:50', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-09 07:22:50\", \"LOGIN\", \"::1\"], [\"2026-02-09 08:20:52\", \"LOGIN\", \"::1\"], [\"2026-02-09 08:21:09\", \"LOGIN\", \"::1\"], [\"2026-02-09 11:30:59\", \"LOGIN\", \"::1\"], [\"2026-02-09 12:06:27\", \"LOGIN\", \"::1\"], [\"2026-02-09 12:06:40\", \"LOGIN\", \"::1\"], [\"2026-02-09 12:07:19\", \"LOGIN\", \"::1\"], [\"2026-02-09 12:09:31\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"e8f43d500261f4169702965c27049335ba84ba7598a439b10acd0cc0f33e112e\", \"edb45c2139cdc194385d6c9c023bb39d57f3cd3db2d46befa7f86bb1b27fb27d\", \"2c885509e8dbb3aafae45f4dda0d1dbab7981efc65232297166a329d4cd8a529\", \"05bb68fd8f610b9d1ffe3c522d9d61f043e65d241e3e2ddb0940f282bc94e5b5\", \"d88ab727bbfe722950d7cf00acec00519062c7270dcd086ff6a525a4e7e0f9e9\", \"6604b7d3f6f647efcc60a0dc051cd0aad54e7c3c837eb977de788aa8cc09fdc1\", \"8b39959522c1b5e4378f8adc0af69f4e6b1a98316cd869e7cccd4d627b590c52\", \"05edbb956474baa6119b0cbd91c0d3d5e19192eddd6b8a98a86f0643dbd210cd\"]', 1, 2),
(28, '2026-02-09 15:20:50', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-09 15:20:50\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"66f2df9ac36ab4cbca0c97f6e8068466431a05baeb2aa30891e35db936167931\"]', 1, 1),
(29, '2026-02-10 06:42:21', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-10 06:42:21\", \"LOGIN\", \"::1\"], [\"2026-02-10 06:49:49\", \"LOGIN\", \"::1\"], [\"2026-02-10 06:50:21\", \"LOGIN\", \"::1\"], [\"2026-02-10 06:52:02\", \"LOGIN\", \"::1\"], [\"2026-02-10 08:11:52\", \"LOGIN\", \"::1\"], [\"2026-02-10 08:38:53\", \"LOGIN\", \"::1\"], [\"2026-02-10 08:39:01\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"8c639d590a9dbe4158dcc3bb7e54e42ea3efbab164746a386b4263ca9d5e9497\", \"a25368c2b942a87e94a8ea0c3f0355f03f0af9b686f691b06a956207138db891\", \"4a98328f9447d47f1ec7e4888d0ebe44737fb700e6a94e1de22e7aca92629844\", \"a57c40431301e658d27ecf453d973b8c74eea99fdd083efd530d45e59012a171\", \"63dd904550e9c795f2be55de27b397ffb95d8235c15a3461804feb4b5f71674b\", \"2117db50104b234aa5d5d3977e6ecf6b39456248a58c35524de877d823f3bfb1\", \"5daa3893dd689dcf48d7617605631a80ec002677005c0928ad2adf11917b0b05\"]', 1, 2),
(30, '2026-02-10 06:42:47', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-10 06:42:47\", \"LOGIN\", \"::1\"], [\"2026-02-10 06:47:44\", \"LOGIN\", \"::1\"], [\"2026-02-10 07:15:48\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"8e2c96a4ba7882a98b13d15faf1a2c5be30602419a04e642b5a60d4feb6ce213\", \"bca8a15d105d00376e73a5d626c8ecdc64acccacb86dad279aa1b6f25678d9dd\", \"fea9b5aac9f2755fd6519800773ebe2273936b5cbd192bef36562ce9fd0e8189\"]', 1, 1),
(31, '2026-02-10 07:32:20', '0000-00-00 00:00:00', 'LOGIN', '3', '[[\"2026-02-10 07:32:20\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"af61b1d26a50d476701d86967c3e9785b1d5366331beee97bda199b2dc7ca089\"]', 1, 2),
(32, '2026-02-11 06:53:46', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-11 06:53:46\", \"LOGIN\", \"::1\"], [\"2026-02-11 10:18:47\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"d021bef5c5ced9226630d1e9d0ab5c3bf0a88d4b24322fde8b1ea096bca1cad6\", \"023f0bffc7d135e7064f669c0c270f1acae5d7cd973e4bc2a364f6f855795873\"]', 1, 2),
(33, '2026-02-11 06:56:25', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-11 06:56:25\", \"LOGIN\", \"::1\"], [\"2026-02-11 09:50:37\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"98734a32557113954f8e3042701530faaecc86e7878b0094652cdd088a7879a1\", \"a1b626be9cb554f103e185233e923367200dec6fdbfa4ffacd84c6f0508e232b\"]', 1, 2),
(34, '2026-02-12 09:24:09', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-12 09:24:09\", \"LOGIN\", \"::1\"], [\"2026-02-12 09:57:21\", \"LOGIN\", \"::1\"], [\"2026-02-12 09:57:41\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"7dfd3cc7ab65f233bf991561d0a434a70e31907e4887c49c60e4b3823cbea33a\", \"ecaaaceb3fd5b9c6aa112816f0b47545bf0932b1990300fe8a4b194d53b9b92f\", \"ce881e912e1e59d0bcbc93138e6609dc1deb37a22a1af64269f7b6c4e205265e\"]', 1, 2),
(35, '2026-02-12 11:56:12', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-12 11:56:12\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"4a75042ce428f3b52b7cb255295c447c125101e53562cfd97dd30f198b559ec0\"]', 1, 1),
(36, '2026-02-13 07:39:57', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-13 07:39:57\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"002e38d067bde8a42fee496c2e930dc344190746b1c9b0857b2f9836c80aafe7\"]', 1, 2),
(37, '2026-02-18 06:30:18', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-18 06:30:18\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"2750356ec357050525ddafeb2f37f537a5247c56afde0d8183264e90271ca553\"]', 1, 2),
(38, '2026-02-18 06:41:34', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-18 06:41:34\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"9a3e854c5915e949c8a8755909daecb5da8423bdb420a5a30a3ef8032ce25fa5\"]', 1, 1),
(39, '2026-02-19 06:58:42', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-19 06:58:42\", \"LOGIN\", \"::1\"], [\"2026-02-19 13:27:56\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"499833f195343e2295bee84624db54177755d08ffb16371ed863a9ba109f1be8\", \"38151cafd5142c7c1f261e41c2c6eba122328652e7d8def7778be1d6ea0ed7d1\"]', 1, 2),
(40, '2026-02-19 06:59:13', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-19 06:59:13\", \"LOGIN\", \"::1\"], [\"2026-02-19 13:27:19\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"29488a6840082c305e84d0569f13bafafefe58c781f00f83cbff36024a42a30b\", \"4e73dd90558e9a46b6673a946ea532de7198dc50cd8e47e40b6cdb1d028361ae\"]', 1, 1),
(41, '2026-02-20 07:25:09', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-20 07:25:09\", \"LOGIN\", \"::1\"], [\"2026-02-20 07:33:56\", \"LOGIN\", \"::1\"], [\"2026-02-20 07:35:37\", \"LOGIN\", \"::1\"], [\"2026-02-20 07:52:01\", \"LOGIN\", \"::1\"], [\"2026-02-20 08:15:48\", \"LOGIN\", \"::1\"], [\"2026-02-20 08:16:41\", \"LOGIN\", \"::1\"], [\"2026-02-20 08:30:35\", \"LOGIN\", \"::1\"], [\"2026-02-20 08:38:33\", \"LOGIN\", \"::1\"], [\"2026-02-20 13:17:30\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"a7f2307db89341b809dc0454b8b84929cd1d982426e050b04d49d91ca314da0b\", \"633011160c2ef335164852d3d003aa638d9cb17c223b94e6a17cf8e0c149c2bc\", \"35d80a02494fba680818c259c01db7936e84f8fcc53e0a01e69d3a2d6237cc81\", \"887790f0594f25bf083aa8d2cb51649569bb2fa0f695cfd682b2f93bd0366e6f\", \"d06df199ed05ab7e38db79346b55c7461b9e48f6259e322ea15f6f8a283acea7\", \"8af70f373ce4bca306d3836a559a84745d1384ad07dd207b0ab2973e4d785224\", \"1ccff07b26a2c762ee8b5fdfe4ea03161b9cb488b3a376ae9b1281e3a1b053f5\", \"e0c3b395825f6c67587935c6ecaa7188a2dab6de36d3270350c3f64e20cdf123\", \"fb81ab1dd4f13107eb049ccdcead783b1ba21cb9c93f37cfdde96e81d9137f8f\"]', 1, 2),
(42, '2026-02-20 08:31:46', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-20 08:31:46\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"9a3e1191b5d60c5facd4205d7fb71f48ee8d999ec9de027207eb7b55118ea6d2\"]', 1, 1),
(43, '2026-02-23 05:49:16', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-23 05:49:16\", \"LOGIN\", \"::1\"], [\"2026-02-23 08:05:54\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"da0ad61c3df43d77f0c0b47439aeb850116d8dfda796d29ca5ec75f5cb6de17e\", \"111b221715f21a444e27944699c43d2922c0550c7ec0463ba7cedcbeabd9a49d\"]', 1, 1),
(44, '2026-02-23 11:51:19', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-23 11:51:19\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"972a4698c89ca6402cc2f79dbccedb0d969987b0dc267e82a9ae4b2080254280\"]', 1, 2),
(45, '2026-02-24 07:01:20', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-24 07:01:20\", \"LOGIN\", \"::1\"], [\"2026-02-24 13:35:56\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"efb4768d9bf83600c56208cf0d1667cc919d01bd639e00b9f4a23e3156492770\", \"19b2ceb17ac6cc2afb864b59f28de8780fb11cb92d5dfaf93842c1bc671df48b\"]', 1, 2),
(46, '2026-02-24 07:02:18', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-24 07:02:18\", \"LOGIN\", \"::1\"], [\"2026-02-24 09:31:45\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"3c9e7ad7fdf9a50d42efa9b226116763d02c681832b458e48611ef2d844825e8\", \"8eacaf29965245b97a7859627168b370dba49d3596eba6e619021284cff3955f\"]', 1, 1),
(47, '2026-02-26 08:18:44', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-26 08:18:44\", \"LOGIN\", \"::1\"], [\"2026-02-26 12:31:43\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Microsoft Edge\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Microsoft Edge 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"84f475b4f96d357c52071c6baede96a8ec8742b7fd9a1cf1ed493c6fd818f168\", \"f160dc160d1ecfa2bb7e3b3b9b6a1ca3ee0efc07fc71aaa6ffd10617c192f814\"]', 1, 2),
(48, '2026-02-26 08:25:22', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-26 08:25:22\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"03dcb8d9c20ca8b3dcd411d4542cef1b53fc1ab1ad3f9a4025151599615e53d6\"]', 1, 1),
(49, '2026-02-27 11:23:36', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-27 11:23:36\", \"LOGIN\", \"::1\"], [\"2026-02-27 11:24:14\", \"LOGIN\", \"::1\"], [\"2026-02-27 12:00:15\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"6541be735280bf86cd2dee11b82e84bc51481b8c2a0fb6f5d4649bae8200a09b\", \"676324d8ef075b00b5aaea6f562b13104c29f1c494a1d4bcc8827ee99c2b73f7\", \"be63e8dccf0be266040eb56390d0d3c53fc311dd5b4776eb10ac65af7dca01e4\"]', 1, 1),
(50, '2026-03-01 00:48:29', '2026-03-01 21:00:03', 'LOGIN', '1', '[[\"2026-03-01 00:48:29\", \"LOGIN\", \"::1\"], [\"2026-03-01 01:12:29\", \"LOGIN\", \"::1\"], [\"2026-03-01 02:06:25\", \"LOGIN\", \"::1\"], [\"2026-03-01 11:26:33\", \"LOGIN\", \"::1\"], [\"2026-03-01 15:36:47\", \"LOGIN\", \"::1\"], [\"2026-03-01 15:40:01\", \"LOGIN\", \"::1\"], [\"2026-03-01 16:16:56\", \"LOGOUT\", \"::1\"], [\"2026-03-01 16:17:04\", \"LOGIN\", \"::1\"], [\"2026-03-01 16:49:00\", \"LOGIN\", \"::1\"], [\"2026-03-01 18:09:34\", \"LOGIN\", \"::1\"], [\"2026-03-01 18:10:08\", \"LOGIN\", \"::1\"], [\"2026-03-01 21:00:01\", \"LOGIN\", \"::1\"], [\"2026-03-01 21:00:03\", \"LOGOUT\", \"::1\"], [\"2026-03-01 21:03:27\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"156b7244b2ca9adc97af204debd3298fb1d77d7b4e36aaaaa5193fe970f75b4e\", \"ba68b5d318f07774efaddda5fe20c634df5800218fdc5ca60b8fe4478beea759\", \"e06cad4d12ccaa58ca3a8ba1262d5fc5682fc9d0d8baac12aad87c93ea923d4b\", \"31f01cdd0e576cc089be02aab95bc4d2c7c2d4a2c2601a510009b29f1a20b01a\", \"c627edd9234dd77c1043988b6d514140a0cfcb13516c5b558a1f10751309f6ae\", \"731af3b8ddb43354523238d8205ff5e195a20a3e5fa5fe337b46b8bf6cd36c17\", \"b74cbc05b018acaa4e753f73a1000bccfebb75ff4bb45797a95ab8e1819b8d86\", \"21435f8b310253bdb3f6c7c4acc4658a5dddaf7a82cef3768d3396cf8c24cc65\", \"5250abd36133626207ef1b8fc1e01c03cd8cc70875506987e5b730db9aaeb230\", \"5928a931b2dd5aa09caa552c749259c7d54b071e0da315b7346d49212b6a83b6\", \"a30d65d493463d2c2f9e599e6e6fce660fd501f1a1a6b876c311439f0a3e179d\", \"a4c6796e19297d50d3a532155fd8e77948989e2fd4bf1cf090df88d1c7b27edb\"]', 1, 1),
(51, '2026-03-03 15:54:15', '2026-03-03 16:47:03', 'LOGIN', '1', '[[\"2026-03-03 15:54:15\", \"LOGIN\", \"::1\"], [\"2026-03-03 15:55:18\", \"LOGOUT\", \"::1\"], [\"2026-03-03 15:55:23\", \"LOGIN\", \"::1\"], [\"2026-03-03 16:46:35\", \"LOGOUT\", \"::1\"], [\"2026-03-03 16:46:52\", \"LOGIN\", \"::1\"], [\"2026-03-03 16:47:03\", \"LOGOUT\", \"::1\"], [\"2026-03-03 16:49:32\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"e88a9998159429316f9f6e697e553515547422b3cdb005f8765e0c38ac5e34b6\", \"f9c480baea99c7d8f186afa280b0d33d22e24f98f84d00c7a2c54e307ac61a14\", \"868e1109da62e050c7e7def9caf10148a92850058c8973559468d8e1fe6739d3\", \"262159065865850dc2dc0cec60a0a784cdfa62ef80d5a2f0d96b6a0161e31ae5\"]', 1, 1),
(52, '2026-03-04 08:35:57', '2026-03-04 16:38:03', 'LOGIN', '1', '[[\"2026-03-04 08:35:57\", \"LOGIN\", \"::1\"], [\"2026-03-04 08:59:40\", \"LOGOUT\", \"::1\"], [\"2026-03-04 08:59:57\", \"LOGIN\", \"::1\"], [\"2026-03-04 09:34:12\", \"LOGOUT\", \"::1\"], [\"2026-03-04 09:34:25\", \"LOGIN\", \"::1\"], [\"2026-03-04 09:40:08\", \"LOGOUT\", \"::1\"], [\"2026-03-04 09:40:14\", \"LOGIN\", \"::1\"], [\"2026-03-04 09:40:46\", \"LOGOUT\", \"::1\"], [\"2026-03-04 09:40:53\", \"LOGIN\", \"::1\"], [\"2026-03-04 09:54:26\", \"LOGIN\", \"::1\"], [\"2026-03-04 09:55:01\", \"LOGOUT\", \"::1\"], [\"2026-03-04 09:55:11\", \"LOGIN\", \"::1\"], [\"2026-03-04 09:55:28\", \"LOGIN\", \"::1\"], [\"2026-03-04 10:42:47\", \"LOGIN\", \"::1\"], [\"2026-03-04 13:43:41\", \"LOGIN\", \"::1\"], [\"2026-03-04 14:45:18\", \"LOGOUT\", \"::1\"], [\"2026-03-04 14:45:27\", \"LOGIN\", \"::1\"], [\"2026-03-04 15:47:55\", \"LOGIN\", \"::1\"], [\"2026-03-04 15:48:21\", \"LOGOUT\", \"::1\"], [\"2026-03-04 15:48:31\", \"LOGIN\", \"::1\"], [\"2026-03-04 15:48:48\", \"LOGOUT\", \"::1\"], [\"2026-03-04 15:48:53\", \"LOGIN\", \"::1\"], [\"2026-03-04 16:38:03\", \"LOGOUT\", \"::1\"], [\"2026-03-04 16:38:08\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"f11cbce7cc0f4aaeec47e976c40b38a3c2315641463d74d7dbeae9aa76581cad\", \"6ff75279e2b5939c8beecafb97c4942483aeae0d860ea1cd62f94c072b626300\", \"8f57567808b263eb442202eb116a33a505151f7fddcd9c377211663edac7b25a\", \"d3484bcc46913b4c98f277ba120df2cf55fe77def9792c6bdfe3bce3bb3dea18\", \"0ea837c4148ab3ef0a10aa593df9eb360837333c721e1b09f8c436bc72b40a55\", \"72db427cd1aba6bced5d9b74cb6034e2897ca902dd7dddc20c81813edbeb7ac5\", \"b13872c14eae31630381c97bd11d54021ee541bc637a384e4e4aa6ead0e42ae6\", \"ad6bd701a5bf0466c54922b13a51b9bf9460c41b1ed4f563bcd922b25e553150\", \"1c6389f4c31db91954d6e3862a27bab034c957da526f6088c306935e2fcc02de\", \"93517acffd6b6bb5bb77df296e33cea3881f596df47fa1421b9af55341b58024\", \"8b2768da90773f586c45b873bf46d74770cc7137eacd09565ee9c589c66d3fbb\", \"4b88f706a88180439941321fb0c41b22fa051ac8274b93d0ee57dc5342f08d6a\", \"a37526ce1d77db0ed6639ff05d8d2db0ed30c7df52ae8f0e780f6262077a8ed0\", \"9574afbdf79b5a533d5545c5c9f044a4b85aaaea504de5f1843429e18d3cd669\", \"34538c2186896fd24d68729756a65146c2cdeaadcaae6ee961ba256bbec2a644\"]', 1, 1),
(53, '2026-03-04 13:31:31', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-03-04 13:31:31\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"d35ba11c3e2dc70467ac989f182f08ff3f09f6c8c26ec24d7ec8a4f3958d6fe5\"]', 1, 3),
(54, '2026-03-05 09:00:16', '2026-03-05 16:12:07', 'LOGIN', '1', '[[\"2026-03-05 09:00:16\", \"LOGIN\", \"::1\"], [\"2026-03-05 09:06:32\", \"LOGOUT\", \"::1\"], [\"2026-03-05 09:06:40\", \"LOGIN\", \"::1\"], [\"2026-03-05 09:13:57\", \"LOGOUT\", \"::1\"], [\"2026-03-05 09:14:06\", \"LOGIN\", \"::1\"], [\"2026-03-05 09:26:35\", \"LOGOUT\", \"::1\"], [\"2026-03-05 10:13:10\", \"LOGIN\", \"::1\"], [\"2026-03-05 10:52:20\", \"LOGIN\", \"::1\"], [\"2026-03-05 11:36:50\", \"LOGOUT\", \"::1\"], [\"2026-03-05 11:36:56\", \"LOGIN\", \"::1\"], [\"2026-03-05 13:19:58\", \"LOGOUT\", \"::1\"], [\"2026-03-05 13:34:48\", \"LOGIN\", \"::1\"], [\"2026-03-05 14:13:18\", \"LOGOUT\", \"::1\"], [\"2026-03-05 14:13:25\", \"LOGIN\", \"::1\"], [\"2026-03-05 15:27:25\", \"LOGOUT\", \"::1\"], [\"2026-03-05 16:03:01\", \"LOGIN\", \"::1\"], [\"2026-03-05 16:12:07\", \"LOGOUT\", \"::1\"], [\"2026-03-05 16:12:13\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"5d42af16c51daf86cebb6ea10690d17bc008309ab75b5fe4abab449216d35f59\", \"bbd33f55aed022e5c31e9155c279846b93de9662d9fa75d82104a28985dc8666\", \"f9d82d25338ccd7982cf91ba439973cad30cc01d37002afb0c13864a6f1aeac0\", \"82a7dfdaf0c38b7a3b8fa7dfe570eac22a8bf869295296510f12b342eb13b505\", \"c44304bcf749e272237fa9e754901119d6dcd2d40e5be4fb03129923c8181c80\", \"903d486109648c7b5823eb6cef91d8dc8ce153b1ac6db764362a29565efd3a3c\", \"1df45ad98d5ef0e38844147382025159b440ef4af56b503cb96649bf1deeeaa3\", \"8bf8026a26f4e7dbcc976166850d93b2f157548ed4aed16da3c4a9e6ed66946d\", \"e00cfd1aa367dc0b0360f5fc718c11c8d6ea1ba9a2f3bc629d74e86c9a61b432\", \"f388242d7d7666a00980186d11571683698f8fd9f3a8ac9d98b3f70d7df9842b\"]', 1, 2),
(55, '2026-03-05 09:29:04', '2026-03-05 15:28:13', 'LOGIN', '3', '[[\"2026-03-05 09:29:04\", \"LOGIN\", \"::1\"], [\"2026-03-05 10:12:54\", \"LOGOUT\", \"::1\"], [\"2026-03-05 15:27:42\", \"LOGIN\", \"::1\"], [\"2026-03-05 15:28:13\", \"LOGOUT\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"bd165d027718856ab630ed9fa03fc60e22d6ad210ecdb5033aa2819d887df257\", \"c80fc9a7a044fb3796f3312973537e714945437399db6e12728f0f7ea7bdfb85\"]', 0, 4),
(56, '2026-03-05 15:28:27', '2026-03-05 16:02:39', 'LOGIN', '2', '[[\"2026-03-05 15:28:27\", \"LOGIN\", \"::1\"], [\"2026-03-05 16:02:39\", \"LOGOUT\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"f294bb96d9c86b7299fd5cc3279e9ca9038bb0828ab71f50f6fb34f73dd71b96\"]', 0, 3),
(57, '2026-03-06 08:03:09', '2026-03-06 13:57:54', 'LOGIN', '1', '[[\"2026-03-06 08:03:09\", \"LOGIN\", \"::1\"], [\"2026-03-06 11:16:08\", \"LOGOUT\", \"::1\"], [\"2026-03-06 11:20:16\", \"LOGIN\", \"::1\"], [\"2026-03-06 13:39:04\", \"LOGOUT\", \"::1\"], [\"2026-03-06 13:39:50\", \"LOGIN\", \"::1\"], [\"2026-03-06 13:57:49\", \"LOGIN\", \"::1\"], [\"2026-03-06 13:57:54\", \"LOGOUT\", \"::1\"], [\"2026-03-06 13:57:59\", \"LOGIN\", \"::1\"], [\"2026-03-06 14:47:46\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"0ca74978563bc25de824f3bda64378c64aa14d6b43dfe35153f03ff383e2e385\", \"106fd3b9b626088423c5e8b1c63b36ad2cf77f0afe32314a38c46846c7f123d3\", \"656f9a37b848165c990ba45e01fe45b74ff974559fac44ad4c4f53d889222ebe\", \"02312ae4ee06e3bd64eb57fcb3f574256074f03fc212665ab0bdb2a4f4779e28\", \"8f7b0ac25be5a1b5834b867d963759566927482ec6db2a46d18dda1d2a615fbe\", \"1a48b9ce6745d8776f1ad3941188fd026b70d2e5fe859ad77bc38bd7dcf784fe\"]', 1, 2),
(58, '2026-03-06 13:39:12', '2026-03-06 13:57:29', 'LOGIN', '2', '[[\"2026-03-06 13:39:12\", \"LOGIN\", \"::1\"], [\"2026-03-06 13:57:29\", \"LOGOUT\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"007a046f923efb25faccdaf10cd370fdd63a9175e8c405c41b73f4ad0171c7d9\"]', 0, 3);
INSERT INTO `user_log` (`user_log_id`, `login_date`, `logout_date`, `action`, `user_id`, `session_id`, `ip_address`, `device`, `system_id`, `token_id`, `login_flag`, `user_level`) VALUES
(59, '2026-03-09 08:58:30', '2026-03-09 11:46:03', 'LOGIN', '1', '[[\"2026-03-09 08:58:30\", \"LOGIN\", \"::1\"], [\"2026-03-09 09:01:58\", \"LOGOUT\", \"::1\"], [\"2026-03-09 09:02:06\", \"LOGIN\", \"::1\"], [\"2026-03-09 09:42:26\", \"LOGOUT\", \"::1\"], [\"2026-03-09 09:42:34\", \"LOGIN\", \"::1\"], [\"2026-03-09 09:43:26\", \"LOGOUT\", \"::1\"], [\"2026-03-09 09:43:31\", \"LOGIN\", \"::1\"], [\"2026-03-09 10:40:30\", \"LOGOUT\", \"::1\"], [\"2026-03-09 10:40:47\", \"LOGIN\", \"::1\"], [\"2026-03-09 10:40:56\", \"LOGOUT\", \"::1\"], [\"2026-03-09 10:49:28\", \"LOGIN\", \"::1\"], [\"2026-03-09 11:00:43\", \"LOGIN\", \"::1\"], [\"2026-03-09 11:02:06\", \"LOGOUT\", \"::1\"], [\"2026-03-09 11:02:14\", \"LOGIN\", \"::1\"], [\"2026-03-09 11:29:35\", \"LOGOUT\", \"::1\"], [\"2026-03-09 11:29:50\", \"LOGIN\", \"::1\"], [\"2026-03-09 11:30:25\", \"LOGOUT\", \"::1\"], [\"2026-03-09 11:30:32\", \"LOGIN\", \"::1\"], [\"2026-03-09 11:31:29\", \"LOGOUT\", \"::1\"], [\"2026-03-09 11:31:39\", \"LOGIN\", \"::1\"], [\"2026-03-09 11:32:00\", \"LOGOUT\", \"::1\"], [\"2026-03-09 11:32:05\", \"LOGIN\", \"::1\"], [\"2026-03-09 11:33:17\", \"LOGIN\", \"::1\"], [\"2026-03-09 11:46:03\", \"LOGOUT\", \"::1\"], [\"2026-03-09 11:46:10\", \"LOGIN\", \"::1\"], [\"2026-03-09 13:22:25\", \"LOGIN\", \"10.10.10.64\"], [\"2026-03-09 13:22:32\", \"LOGIN\", \"::1\"], [\"2026-03-09 14:34:30\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"f8088e4294b1c0f06dc98f723bcbe803a70ddc6b24dd332317ceec49d8bfbeb9\", \"ae645ee2e6bea8de525832582fe7b0f9ec99ad4d9b384b0ace598214bd4e1a48\", \"9163b63e39d2e27679d295de6153214c8814e04dcf9d412e6b11e882bccb6fa0\", \"c0244f09c3746500b687ab57927dffde33772318224260d7939c3191a210c262\", \"0c302eff5ec6d4892075f798fc46109a2bb113f6f61077e944a5dadd2ca3f7bf\", \"37ac2eb10e788b0b426ce89939d5e58c2304c1501ead30d0fca8121f46d04eb8\", \"74276a977871aa437a26843d382638aeef115585a85fa1865558d10d15a00e95\", \"d91395d3809f6442febe29bfef98678612c2de6a65cdc28d63e6d1010c4ac5ec\", \"ef3a08d5fdd63af26b6acc7ad94ed9f71da8d0121695a86a451b15241d8836f5\", \"571d25922ca66d8d13c74c040b5f943df7c197f9bcb9555dd423344d37574c27\", \"bb8ba6b82f819b8095e185ed561440f59ddf0b0f33396512150205530fb3dcf8\", \"72736ec90a6809f69e6489a1f52df9242ce52a89328eddf7f4635efc3d1f7d28\", \"a68a242254747432af536e2c974602a9f29bf78f90fbbd23763e25dee8018fef\", \"c79bd448d59cdccb3a227831b97b4bbe226e06a1b3ac12d73ee312a229a5a302\", \"3ae2e45344fad23c6b5309cdc46edc919bb48cb53277c56e89153dc9c7bb49f7\", \"5b19c2c7076ea278c1383d56219010cd566f4e2849518848e5a738c98a92c773\", \"3b523865270bec55dcea6de2afdbf8a23d37cc4f0d04a8ac15015e18e51a74e3\"]', 1, 2),
(60, '2026-03-09 09:41:35', '0000-00-00 00:00:00', 'LOGIN', '3', '[[\"2026-03-09 09:41:35\", \"LOGIN\", \"::1\"], [\"2026-03-09 20:38:51\", \"LOGIN\", \"::1\"], [\"2026-03-09 22:56:39\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"a10d471f29dda0f135e4a9414be9c3b019887e4bad0fa0db7238f9890e27b77d\", \"ad8dda8391c17fbb82c772f76144bf775a9ceed55f6b8e559613fa09b56f07b5\", \"66c349c033a3caceeb3754b08e5db6c6f49233e0805ad5cd276f2782cfe6eb28\"]', 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `user_log_events`
--

CREATE TABLE `user_log_events` (
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `event_time` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `device` text DEFAULT NULL,
  `session_token` varchar(255) NOT NULL DEFAULT '',
  `user_level` varchar(100) NOT NULL DEFAULT '',
  `source` varchar(50) NOT NULL DEFAULT 'system'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_log_events`
--

INSERT INTO `user_log_events` (`event_id`, `user_id`, `action`, `event_time`, `ip_address`, `device`, `session_token`, `user_level`, `source`) VALUES
(1, 4, 'LOGIN', '2026-02-24 09:37:41', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'fa4802a9c6cdbbe89c10da9be72ae92df74abb01033087d4eb747e3dd62a6f2e', '3', 'system'),
(2, 4, 'LOGIN', '2026-02-24 09:38:07', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'a466662736b9178563f9492641d0ee042fe5506616689d61532efefa71e474fa', '3', 'system'),
(3, 4, 'LOGIN', '2026-02-24 09:41:40', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '96de45e53fe0a89451f8620aa70acaca6cf8a5fc7f037df463b23a3884a7f8aa', '3', 'system'),
(4, 4, 'LOGOUT', '2026-02-24 09:41:42', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '96de45e53fe0a89451f8620aa70acaca6cf8a5fc7f037df463b23a3884a7f8aa', 'ADMIN_STAFF', 'system'),
(5, 4, 'LOGIN', '2026-02-24 09:42:01', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '51d5d11267077e9c6639c4b7c94334b4b940016f6051d6b3144fa95c0fb5470b', '3', 'system'),
(6, 1, 'LOGIN', '2026-02-24 10:36:17', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'dda9f33fdb9726d9666bf3c4f4a9dbb5fcc97b7628473d96301e9b54d4e763fa', '1', 'system'),
(7, 1, 'LOGOUT', '2026-02-24 11:39:11', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'dda9f33fdb9726d9666bf3c4f4a9dbb5fcc97b7628473d96301e9b54d4e763fa', 'SUPER_ADMIN', 'system'),
(8, 1, 'LOGIN', '2026-02-24 11:39:29', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'a4d01a63089ad89654a68f6c87e6588cac7285b03762b00ecbae3692d66a972d', '2', 'system'),
(9, 1, 'LOGOUT', '2026-02-24 13:22:57', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'a4d01a63089ad89654a68f6c87e6588cac7285b03762b00ecbae3692d66a972d', 'ADMIN', 'system'),
(10, 1, 'LOGIN', '2026-02-24 13:23:21', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '84cda3da6f4983011cc22b270e49f6024ab209be8faf6ab05a1efb2db35325f4', '1', 'system'),
(11, 1, 'LOGIN', '2026-02-24 13:25:47', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'd033259ec35ab3d49c3473d58d07dd78b5949962f797ffb0cee596fa3be7c840', '1', 'system'),
(12, 1, 'LOGIN', '2026-02-24 13:26:01', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'c5aba65acf0ff1cb28dd096302d9032f817c26eab3ccb299faee151c7986af09', '1', 'system'),
(13, 1, 'LOGIN', '2026-02-24 14:24:54', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'caa6a5bc9aaa925352f9e138073c0faa675d9337ff61917a47fb15ea9b8ea3d9', '1', 'system'),
(14, 1, 'LOGOUT', '2026-02-24 14:25:25', '127.0.0.1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'caa6a5bc9aaa925352f9e138073c0faa675d9337ff61917a47fb15ea9b8ea3d9', 'SUPER_ADMIN', 'system'),
(15, 3, 'LOGIN', '2026-02-24 14:25:37', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '94da5d2ff0bf7bd6da19004636569f2d9123afad524440ab09bef3869329493e', '3', 'system'),
(16, 1, 'LOGIN', '2026-02-24 14:25:53', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '1f0f1bc3b8c9a3d67f307c0f049daa237002a5359dbf2c76a565000246261a4d', '1', 'system'),
(17, 4, 'LOGIN', '2026-02-24 14:26:34', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'e18780195f9328b364d1fa3727631fd5c96dedeb5d7f887837c2cad493e6bc90', '3', 'system'),
(18, 4, 'LOGOUT', '2026-02-24 14:26:39', '127.0.0.1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'e18780195f9328b364d1fa3727631fd5c96dedeb5d7f887837c2cad493e6bc90', 'ADMIN_STAFF', 'system'),
(19, 1, 'LOGIN', '2026-02-24 14:26:49', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'dbc510cd3891d5c32b6a27fd160abcac8337ddfa94a5bbe55b8ca70da1f95542', '1', 'system'),
(20, 1, 'LOGOUT', '2026-02-24 16:15:32', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'dbc510cd3891d5c32b6a27fd160abcac8337ddfa94a5bbe55b8ca70da1f95542', 'SUPER_ADMIN', 'system'),
(21, 1, 'LOGIN', '2026-02-24 16:15:44', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '30c4b893b1d1ae66a37c4616c3c81bddd7b0f3aa565da8193f8e41c3203d857a', '1', 'system'),
(22, 1, 'LOGOUT', '2026-02-24 16:22:13', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '30c4b893b1d1ae66a37c4616c3c81bddd7b0f3aa565da8193f8e41c3203d857a', 'SUPER_ADMIN', 'system'),
(23, 1, 'LOGIN', '2026-02-24 16:22:24', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '747ff8fa80b7ad621d5331b903c7245edf25dc00f498c77dfb1be1e9b2882642', '2', 'system'),
(24, 1, 'LOGOUT', '2026-02-24 16:45:17', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '747ff8fa80b7ad621d5331b903c7245edf25dc00f498c77dfb1be1e9b2882642', 'ADMIN', 'system'),
(25, 5, 'LOGIN', '2026-02-24 16:45:28', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '0287d64e4e32d87783cfc8f72cc7a07eece52248f9ec8e57c25a644557949ea5', '4', 'system'),
(26, 1, 'LOGIN', '2026-02-26 08:34:23', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '5e4ade248ed3b7985dbada6155f2882d3b5930e03c12020605ad17958ccc81a8', '3', 'system'),
(27, 1, 'LOGIN', '2026-02-26 08:34:32', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'cc2a361e67b9b2cd56e5d4217f8160b67b52c9935861ca613bfc2460ca688f10', '1', 'system'),
(28, 1, 'LOGIN', '2026-02-26 11:24:32', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '6c2b1380f3194e138d1efd660bbe5ec0f61db8885c42950792bf25df773a6a75', '2', 'system'),
(29, 1, 'LOGIN', '2026-02-26 11:30:11', '10.10.10.46', '{\"device\":\"Chrome Mobile\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":32,\"family\":\"Android\",\"version\":\"10\"},\"description\":\"Chrome Mobile 145.0.0.0 on K (Android 10)\"}', '21a98e1c1fca942aaf2fe848511ee317d75e4424f352e466c262c59054be1ea9', '2', 'system'),
(30, 1, 'LOGOUT', '2026-02-26 11:41:31', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '6c2b1380f3194e138d1efd660bbe5ec0f61db8885c42950792bf25df773a6a75', 'ADMIN', 'system'),
(31, 1, 'LOGIN', '2026-02-26 11:41:35', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '6a97804baea3e61308a45cdf4bbeef31881145b8d7d0021a83ef3507ce6bf762', '1', 'system'),
(32, 1, 'LOGOUT', '2026-02-26 11:51:59', '10.10.10.49', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '6a97804baea3e61308a45cdf4bbeef31881145b8d7d0021a83ef3507ce6bf762', 'SUPER_ADMIN', 'system'),
(33, 1, 'LOGIN', '2026-02-26 11:52:03', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'af414c85720a69c3d7b6aa655c3336a4ec0c0285d3889f0f44fd2f11a01438b0', '2', 'system'),
(34, 1, 'LOGIN', '2026-02-26 18:02:05', '10.10.10.49', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '441a0d10575b4253ab40fb6b3ff247b4698bb5e49d7a244a942b42577846f363', '2', 'system'),
(35, 1, 'LOGIN', '2026-02-26 18:04:36', '10.10.10.49', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'e39f4da055508d3c02a042a4b36e3b47c45adc1623a57d326c1ea02641197392', '2', 'system'),
(36, 1, 'LOGIN', '2026-02-26 18:05:09', '10.10.10.46', '{\"device\":\"Chrome Mobile\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":32,\"family\":\"Android\",\"version\":\"10\"},\"description\":\"Chrome Mobile 145.0.0.0 on K (Android 10)\"}', 'dfa2533130a5297f420b596f8ad00983abb299afbd128ae93b11d30489251285', '2', 'system'),
(37, 1, 'LOGIN', '2026-02-26 18:05:43', '10.10.10.49', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'fdbe8acde3bafae0dfce06f285f82fd9384942be6e15f3e2dd54d5de8649516a', '2', 'system'),
(38, 1, 'LOGIN', '2026-02-27 08:20:13', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'af1092c6ea9feadf826e87efc9f928002308ae9acd5ad811984083d283fd9ef7', '2', 'system'),
(39, 1, 'LOGIN', '2026-02-27 10:22:56', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'f31599ed217cc046fb18b077141d0856f1eb05f0ebd5868c4558d7a04a23e510', '1', 'system'),
(40, 1, 'LOGIN', '2026-02-27 11:20:37', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'ecf68ec957be12971883399f80381ecdfec47712e80ccea9c82320022038450c', '2', 'system'),
(41, 1, 'LOGIN', '2026-02-27 11:21:06', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '55f85baf9664aa7aeca0d1f06e5297dd22e9f5d67d33e28ccc699dcd0a2c611a', '1', 'system'),
(42, 1, 'LOGIN', '2026-03-02 08:56:14', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'ed5ed4c8453b2e5bc0ca382aa027a6b59669665f4215621d8b64248739507ce5', '1', 'system'),
(43, 1, 'LOGOUT', '2026-03-02 09:31:03', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'ed5ed4c8453b2e5bc0ca382aa027a6b59669665f4215621d8b64248739507ce5', 'SUPER_ADMIN', 'system'),
(44, 1, 'LOGIN', '2026-03-02 09:31:08', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'b26a1cbc4c17250071007880bed747bc891528bd6318fe75b58267a7db58c73c', '2', 'system'),
(45, 1, 'LOGIN', '2026-03-04 15:47:55', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '4b88f706a88180439941321fb0c41b22fa051ac8274b93d0ee57dc5342f08d6a', '2', 'legacy_sync'),
(46, 1, 'LOGOUT', '2026-03-04 15:48:21', '::1', '', '4b88f706a88180439941321fb0c41b22fa051ac8274b93d0ee57dc5342f08d6a', '0', 'legacy_sync'),
(47, 1, 'LOGIN', '2026-03-04 15:48:31', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'a37526ce1d77db0ed6639ff05d8d2db0ed30c7df52ae8f0e780f6262077a8ed0', '1', 'legacy_sync'),
(48, 1, 'LOGOUT', '2026-03-04 15:48:48', '::1', '', 'a37526ce1d77db0ed6639ff05d8d2db0ed30c7df52ae8f0e780f6262077a8ed0', '0', 'legacy_sync'),
(49, 1, 'LOGIN', '2026-03-04 15:48:53', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '9574afbdf79b5a533d5545c5c9f044a4b85aaaea504de5f1843429e18d3cd669', '1', 'legacy_sync'),
(50, 1, 'LOGOUT', '2026-03-04 16:38:03', '::1', '', '9574afbdf79b5a533d5545c5c9f044a4b85aaaea504de5f1843429e18d3cd669', '1', 'legacy_sync'),
(51, 1, 'LOGIN', '2026-03-04 16:38:08', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '34538c2186896fd24d68729756a65146c2cdeaadcaae6ee961ba256bbec2a644', '1', 'legacy_sync'),
(52, 1, 'LOGIN', '2026-03-05 09:00:16', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '5d42af16c51daf86cebb6ea10690d17bc008309ab75b5fe4abab449216d35f59', '1', 'legacy_sync'),
(53, 1, 'LOGOUT', '2026-03-05 09:06:32', '::1', '', '5d42af16c51daf86cebb6ea10690d17bc008309ab75b5fe4abab449216d35f59', '1', 'legacy_sync'),
(54, 1, 'LOGIN', '2026-03-05 09:06:40', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'bbd33f55aed022e5c31e9155c279846b93de9662d9fa75d82104a28985dc8666', '1', 'legacy_sync'),
(55, 1, 'LOGOUT', '2026-03-05 09:13:57', '::1', '', 'bbd33f55aed022e5c31e9155c279846b93de9662d9fa75d82104a28985dc8666', '1', 'legacy_sync'),
(56, 1, 'LOGIN', '2026-03-05 09:14:06', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'f9d82d25338ccd7982cf91ba439973cad30cc01d37002afb0c13864a6f1aeac0', '2', 'legacy_sync'),
(57, 1, 'LOGOUT', '2026-03-05 09:26:35', '::1', '', 'f9d82d25338ccd7982cf91ba439973cad30cc01d37002afb0c13864a6f1aeac0', '1', 'legacy_sync'),
(58, 3, 'LOGIN', '2026-03-05 09:29:04', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'bd165d027718856ab630ed9fa03fc60e22d6ad210ecdb5033aa2819d887df257', '4', 'legacy_sync'),
(59, 3, 'LOGOUT', '2026-03-05 10:12:54', '::1', '', 'bd165d027718856ab630ed9fa03fc60e22d6ad210ecdb5033aa2819d887df257', '4', 'legacy_sync'),
(60, 1, 'LOGIN', '2026-03-05 10:13:10', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '82a7dfdaf0c38b7a3b8fa7dfe570eac22a8bf869295296510f12b342eb13b505', '2', 'legacy_sync'),
(61, 1, 'LOGIN', '2026-03-05 10:52:20', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'c44304bcf749e272237fa9e754901119d6dcd2d40e5be4fb03129923c8181c80', '2', 'legacy_sync'),
(62, 1, 'LOGOUT', '2026-03-05 11:36:50', '::1', '', 'c44304bcf749e272237fa9e754901119d6dcd2d40e5be4fb03129923c8181c80', '1', 'legacy_sync'),
(63, 1, 'LOGIN', '2026-03-05 11:36:56', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '903d486109648c7b5823eb6cef91d8dc8ce153b1ac6db764362a29565efd3a3c', '1', 'legacy_sync'),
(64, 1, 'LOGOUT', '2026-03-05 13:19:58', '::1', '', '903d486109648c7b5823eb6cef91d8dc8ce153b1ac6db764362a29565efd3a3c', '1', 'legacy_sync'),
(65, 1, 'LOGIN', '2026-03-05 13:34:48', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '1df45ad98d5ef0e38844147382025159b440ef4af56b503cb96649bf1deeeaa3', '1', 'legacy_sync'),
(66, 1, 'LOGOUT', '2026-03-05 14:13:18', '::1', '', '1df45ad98d5ef0e38844147382025159b440ef4af56b503cb96649bf1deeeaa3', '1', 'legacy_sync'),
(67, 1, 'LOGIN', '2026-03-05 14:13:25', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '8bf8026a26f4e7dbcc976166850d93b2f157548ed4aed16da3c4a9e6ed66946d', '2', 'legacy_sync'),
(68, 1, 'LOGOUT', '2026-03-05 15:27:25', '::1', '', '8bf8026a26f4e7dbcc976166850d93b2f157548ed4aed16da3c4a9e6ed66946d', '1', 'legacy_sync'),
(69, 3, 'LOGIN', '2026-03-05 15:27:42', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'c80fc9a7a044fb3796f3312973537e714945437399db6e12728f0f7ea7bdfb85', '4', 'legacy_sync'),
(70, 3, 'LOGOUT', '2026-03-05 15:28:13', '::1', '', 'c80fc9a7a044fb3796f3312973537e714945437399db6e12728f0f7ea7bdfb85', '4', 'legacy_sync'),
(71, 2, 'LOGIN', '2026-03-05 15:28:27', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'f294bb96d9c86b7299fd5cc3279e9ca9038bb0828ab71f50f6fb34f73dd71b96', '3', 'legacy_sync'),
(72, 2, 'LOGOUT', '2026-03-05 16:02:39', '::1', '', 'f294bb96d9c86b7299fd5cc3279e9ca9038bb0828ab71f50f6fb34f73dd71b96', '3', 'legacy_sync'),
(73, 1, 'LOGIN', '2026-03-05 16:03:01', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'e00cfd1aa367dc0b0360f5fc718c11c8d6ea1ba9a2f3bc629d74e86c9a61b432', '1', 'legacy_sync'),
(74, 1, 'LOGOUT', '2026-03-05 16:12:07', '::1', '', 'e00cfd1aa367dc0b0360f5fc718c11c8d6ea1ba9a2f3bc629d74e86c9a61b432', '1', 'legacy_sync'),
(75, 1, 'LOGIN', '2026-03-05 16:12:13', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'f388242d7d7666a00980186d11571683698f8fd9f3a8ac9d98b3f70d7df9842b', '2', 'legacy_sync'),
(76, 1, 'LOGIN', '2026-03-06 08:03:09', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '0ca74978563bc25de824f3bda64378c64aa14d6b43dfe35153f03ff383e2e385', '2', 'legacy_sync'),
(77, 1, 'LOGOUT', '2026-03-06 11:16:08', '::1', '', '0ca74978563bc25de824f3bda64378c64aa14d6b43dfe35153f03ff383e2e385', '1', 'legacy_sync'),
(78, 1, 'LOGIN', '2026-03-06 11:20:16', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '106fd3b9b626088423c5e8b1c63b36ad2cf77f0afe32314a38c46846c7f123d3', '2', 'legacy_sync'),
(79, 1, 'LOGOUT', '2026-03-06 13:39:04', '::1', '', '106fd3b9b626088423c5e8b1c63b36ad2cf77f0afe32314a38c46846c7f123d3', '1', 'legacy_sync'),
(80, 2, 'LOGIN', '2026-03-06 13:39:12', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '007a046f923efb25faccdaf10cd370fdd63a9175e8c405c41b73f4ad0171c7d9', '3', 'legacy_sync'),
(81, 1, 'LOGIN', '2026-03-06 13:39:50', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '656f9a37b848165c990ba45e01fe45b74ff974559fac44ad4c4f53d889222ebe', '1', 'legacy_sync'),
(82, 2, 'LOGOUT', '2026-03-06 13:57:29', '::1', '', '007a046f923efb25faccdaf10cd370fdd63a9175e8c405c41b73f4ad0171c7d9', '3', 'legacy_sync'),
(83, 1, 'LOGIN', '2026-03-06 13:57:49', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '02312ae4ee06e3bd64eb57fcb3f574256074f03fc212665ab0bdb2a4f4779e28', '1', 'legacy_sync'),
(84, 1, 'LOGOUT', '2026-03-06 13:57:54', '::1', '', '02312ae4ee06e3bd64eb57fcb3f574256074f03fc212665ab0bdb2a4f4779e28', '1', 'legacy_sync'),
(85, 1, 'LOGIN', '2026-03-06 13:57:59', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '8f7b0ac25be5a1b5834b867d963759566927482ec6db2a46d18dda1d2a615fbe', '2', 'legacy_sync'),
(86, 1, 'LOGIN', '2026-03-06 14:47:46', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '1a48b9ce6745d8776f1ad3941188fd026b70d2e5fe859ad77bc38bd7dcf784fe', '2', 'legacy_sync'),
(87, 1, 'LOGIN', '2026-03-09 08:58:30', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'f8088e4294b1c0f06dc98f723bcbe803a70ddc6b24dd332317ceec49d8bfbeb9', '1', 'legacy_sync'),
(88, 1, 'LOGOUT', '2026-03-09 09:01:58', '::1', '', 'f8088e4294b1c0f06dc98f723bcbe803a70ddc6b24dd332317ceec49d8bfbeb9', '1', 'legacy_sync'),
(89, 1, 'LOGIN', '2026-03-09 09:02:06', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'ae645ee2e6bea8de525832582fe7b0f9ec99ad4d9b384b0ace598214bd4e1a48', '2', 'legacy_sync'),
(90, 3, 'LOGIN', '2026-03-09 09:41:35', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'a10d471f29dda0f135e4a9414be9c3b019887e4bad0fa0db7238f9890e27b77d', '4', 'legacy_sync'),
(91, 1, 'LOGOUT', '2026-03-09 09:42:26', '::1', '', 'ae645ee2e6bea8de525832582fe7b0f9ec99ad4d9b384b0ace598214bd4e1a48', '1', 'legacy_sync'),
(92, 1, 'LOGIN', '2026-03-09 09:42:34', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '9163b63e39d2e27679d295de6153214c8814e04dcf9d412e6b11e882bccb6fa0', '1', 'legacy_sync'),
(93, 1, 'LOGOUT', '2026-03-09 09:43:26', '::1', '', '9163b63e39d2e27679d295de6153214c8814e04dcf9d412e6b11e882bccb6fa0', '1', 'legacy_sync'),
(94, 1, 'LOGIN', '2026-03-09 09:43:31', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'c0244f09c3746500b687ab57927dffde33772318224260d7939c3191a210c262', '2', 'legacy_sync'),
(95, 1, 'LOGOUT', '2026-03-09 10:40:30', '::1', '', 'c0244f09c3746500b687ab57927dffde33772318224260d7939c3191a210c262', '1', 'legacy_sync'),
(96, 1, 'LOGIN', '2026-03-09 10:40:47', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '0c302eff5ec6d4892075f798fc46109a2bb113f6f61077e944a5dadd2ca3f7bf', '1', 'legacy_sync'),
(97, 1, 'LOGOUT', '2026-03-09 10:40:56', '::1', '', '0c302eff5ec6d4892075f798fc46109a2bb113f6f61077e944a5dadd2ca3f7bf', '1', 'legacy_sync'),
(98, 1, 'LOGIN', '2026-03-09 10:49:28', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '37ac2eb10e788b0b426ce89939d5e58c2304c1501ead30d0fca8121f46d04eb8', '2', 'legacy_sync'),
(99, 1, 'LOGIN', '2026-03-09 11:00:43', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '74276a977871aa437a26843d382638aeef115585a85fa1865558d10d15a00e95', '1', 'legacy_sync'),
(100, 1, 'LOGOUT', '2026-03-09 11:02:06', '::1', '', '74276a977871aa437a26843d382638aeef115585a85fa1865558d10d15a00e95', '1', 'legacy_sync'),
(101, 1, 'LOGIN', '2026-03-09 11:02:14', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'd91395d3809f6442febe29bfef98678612c2de6a65cdc28d63e6d1010c4ac5ec', '2', 'legacy_sync'),
(102, 1, 'LOGOUT', '2026-03-09 11:29:35', '::1', '', 'd91395d3809f6442febe29bfef98678612c2de6a65cdc28d63e6d1010c4ac5ec', '1', 'legacy_sync'),
(103, 1, 'LOGIN', '2026-03-09 11:29:50', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'ef3a08d5fdd63af26b6acc7ad94ed9f71da8d0121695a86a451b15241d8836f5', '1', 'legacy_sync'),
(104, 1, 'LOGOUT', '2026-03-09 11:30:25', '::1', '', 'ef3a08d5fdd63af26b6acc7ad94ed9f71da8d0121695a86a451b15241d8836f5', '1', 'legacy_sync'),
(105, 1, 'LOGIN', '2026-03-09 11:30:32', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '571d25922ca66d8d13c74c040b5f943df7c197f9bcb9555dd423344d37574c27', '1', 'legacy_sync'),
(106, 1, 'LOGOUT', '2026-03-09 11:31:29', '::1', '', '571d25922ca66d8d13c74c040b5f943df7c197f9bcb9555dd423344d37574c27', '1', 'legacy_sync'),
(107, 1, 'LOGIN', '2026-03-09 11:31:39', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'bb8ba6b82f819b8095e185ed561440f59ddf0b0f33396512150205530fb3dcf8', '1', 'legacy_sync'),
(108, 1, 'LOGOUT', '2026-03-09 11:32:00', '::1', '', 'bb8ba6b82f819b8095e185ed561440f59ddf0b0f33396512150205530fb3dcf8', '1', 'legacy_sync'),
(109, 1, 'LOGIN', '2026-03-09 11:32:05', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '72736ec90a6809f69e6489a1f52df9242ce52a89328eddf7f4635efc3d1f7d28', '1', 'legacy_sync'),
(110, 1, 'LOGIN', '2026-03-09 11:33:17', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'a68a242254747432af536e2c974602a9f29bf78f90fbbd23763e25dee8018fef', '1', 'legacy_sync'),
(111, 1, 'LOGOUT', '2026-03-09 11:46:03', '::1', '', 'a68a242254747432af536e2c974602a9f29bf78f90fbbd23763e25dee8018fef', '1', 'legacy_sync'),
(112, 1, 'LOGIN', '2026-03-09 11:46:10', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'c79bd448d59cdccb3a227831b97b4bbe226e06a1b3ac12d73ee312a229a5a302', '2', 'legacy_sync'),
(113, 1, 'LOGIN', '2026-03-09 13:22:25', '10.10.10.64', '{\"device\":\"Chrome Mobile\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":32,\"family\":\"Android\",\"version\":\"10\"},\"description\":\"Chrome Mobile 145.0.0.0 on K (Android 10)\"}', '3ae2e45344fad23c6b5309cdc46edc919bb48cb53277c56e89153dc9c7bb49f7', '2', 'legacy_sync'),
(114, 1, 'LOGIN', '2026-03-09 13:22:32', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '5b19c2c7076ea278c1383d56219010cd566f4e2849518848e5a738c98a92c773', '2', 'legacy_sync'),
(115, 1, 'LOGIN', '2026-03-09 14:34:30', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '3b523865270bec55dcea6de2afdbf8a23d37cc4f0d04a8ac15015e18e51a74e3', '2', 'legacy_sync'),
(116, 3, 'LOGIN', '2026-03-09 20:38:51', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 'ad8dda8391c17fbb82c772f76144bf775a9ceed55f6b8e559613fa09b56f07b5', '4', 'legacy_sync'),
(117, 3, 'LOGIN', '2026-03-09 22:56:39', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', '66c349c033a3caceeb3754b08e5db6c6f49233e0805ad5cd276f2782cfe6eb28', '4', 'legacy_sync');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`activity_log_id`);

--
-- Indexes for table `ast_audit_checks`
--
ALTER TABLE `ast_audit_checks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_session_property` (`session_id`,`property_code`),
  ADD KEY `idx_audit_session` (`session_id`),
  ADD KEY `idx_audit_property` (`property_id`),
  ADD KEY `idx_audit_checked_by` (`checked_by`);

--
-- Indexes for table `ast_audit_sessions`
--
ALTER TABLE `ast_audit_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_series_code` (`series_code`),
  ADD KEY `idx_audit_status` (`status`),
  ADD KEY `idx_audit_created_by` (`created_by`);

--
-- Indexes for table `ast_inventory`
--
ALTER TABLE `ast_inventory`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `uniq_property_code` (`property_code`),
  ADD UNIQUE KEY `uniq_property_series` (`property_number`,`property_series`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Indexes for table `ast_inventory_category`
--
ALTER TABLE `ast_inventory_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `csm_inventory`
--
ALTER TABLE `csm_inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD UNIQUE KEY `inventory_system_item_code` (`inventory_system_item_code`);

--
-- Indexes for table `csm_inventory_category`
--
ALTER TABLE `csm_inventory_category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `item_category_code` (`item_category_code`),
  ADD UNIQUE KEY `uniq_item_category_code` (`item_category_code`);

--
-- Indexes for table `csm_inventory_category_images`
--
ALTER TABLE `csm_inventory_category_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Indexes for table `employment_status`
--
ALTER TABLE `employment_status`
  ADD PRIMARY KEY (`employment_status_id`),
  ADD UNIQUE KEY `status_code` (`status_code`);

--
-- Indexes for table `facility_records_assignments`
--
ALTER TABLE `facility_records_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_assignment_unit` (`unit_id`),
  ADD KEY `idx_assignment_status` (`status`),
  ADD KEY `idx_assignment_module` (`module_type`);

--
-- Indexes for table `facility_records_facilities`
--
ALTER TABLE `facility_records_facilities`
  ADD PRIMARY KEY (`facility_id`),
  ADD UNIQUE KEY `uk_facility_code` (`facility_code`);

--
-- Indexes for table `facility_records_history`
--
ALTER TABLE `facility_records_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_history_assignment` (`assignment_id`);

--
-- Indexes for table `facility_records_units`
--
ALTER TABLE `facility_records_units`
  ADD PRIMARY KEY (`unit_id`),
  ADD UNIQUE KEY `uk_facility_unit_code` (`facility_id`,`unit_code`),
  ADD KEY `idx_unit_facility` (`facility_id`);

--
-- Indexes for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD PRIMARY KEY (`requisition_id`),
  ADD KEY `idx_requisition_module_status` (`module_type`,`status`),
  ADD KEY `idx_requisition_requester` (`requester_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_users_employment_status_id` (`employment_status_id`);

--
-- Indexes for table `user_access`
--
ALTER TABLE `user_access`
  ADD PRIMARY KEY (`user_access_id`),
  ADD UNIQUE KEY `user_access_unique` (`user_id`,`access_code`),
  ADD KEY `user_access_user_id` (`user_id`);

--
-- Indexes for table `user_log`
--
ALTER TABLE `user_log`
  ADD PRIMARY KEY (`user_log_id`);

--
-- Indexes for table `user_log_events`
--
ALTER TABLE `user_log_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_time` (`event_time`),
  ADD KEY `idx_action` (`action`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `activity_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `ast_audit_checks`
--
ALTER TABLE `ast_audit_checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ast_audit_sessions`
--
ALTER TABLE `ast_audit_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ast_inventory`
--
ALTER TABLE `ast_inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `ast_inventory_category`
--
ALTER TABLE `ast_inventory_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `csm_inventory`
--
ALTER TABLE `csm_inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `csm_inventory_category`
--
ALTER TABLE `csm_inventory_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `csm_inventory_category_images`
--
ALTER TABLE `csm_inventory_category_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employment_status`
--
ALTER TABLE `employment_status`
  MODIFY `employment_status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `facility_records_assignments`
--
ALTER TABLE `facility_records_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `facility_records_facilities`
--
ALTER TABLE `facility_records_facilities`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `facility_records_history`
--
ALTER TABLE `facility_records_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `facility_records_units`
--
ALTER TABLE `facility_records_units`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `requisition_items`
--
ALTER TABLE `requisition_items`
  MODIFY `requisition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_access`
--
ALTER TABLE `user_access`
  MODIFY `user_access_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_log`
--
ALTER TABLE `user_log`
  MODIFY `user_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `user_log_events`
--
ALTER TABLE `user_log_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_employment_status` FOREIGN KEY (`employment_status_id`) REFERENCES `employment_status` (`employment_status_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_access`
--
ALTER TABLE `user_access`
  ADD CONSTRAINT `user_access_fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

