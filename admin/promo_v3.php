<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$APPLICATION->SetTitle(GetMessage('VCH_PROMO_TITLE'));

echo GetMessage('VCH_PROMO_PAGE');
?>

<style>

.vch-promo-preview {
	margin-top: 2em;
}
.vch-promo-preview .vch-promo-poster {
	max-width: 190px;
	margin-top: 10em;
	margin-right: 3em;
	margin-bottom: 2em;
	float: left;
}
.vch-promo-preview ul {
	list-style: none;
	padding: 0;
	margin-top: 2rem;
	columns: 2;
	-webkit-columns: 2;
	-moz-columns: 2;
}
.vch-promo-preview ul li {
	margin: 0.8em auto;
	font-size: 110%;
	display: inline-block;
}
.vch-promo-preview ul li:before {
	content: "";
	display: inline-block;
	height: 1em;
	width: 1em;
	margin-right: 1em;
	border-radius: 50%;
	background: rgb(236,70,145);
	background: -moz-linear-gradient(42deg, rgba(236,70,145,1) 0%, rgba(251,209,40,1) 100%);
	background: -webkit-linear-gradient(42deg, rgba(236,70,145,1) 0%, rgba(251,209,40,1) 100%);
	background: linear-gradient(42deg, rgba(236,70,145,1) 0%, rgba(251,209,40,1) 100%);
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#ec4691",endColorstr="#fbd128",GradientType=1);
	vertical-align: middle;
}
.vch-promo-preview .vch-promo-btn {
	width: 610px;
}

.vch-promo-btn {
	display: inline-block;
	padding: 1rem 3rem;
	font-size: 110%;
	background-color: #FBD727;
	color: #25282C;
	text-transform: uppercase;
	text-decoration: none;
	border-radius: 4px;
	margin-top: 1rem;
	font-weight: bold;
	text-align: center;
}
.vch-promo-btn:hover {
	background-color: #FFD81A;
	text-decoration: none;
	box-shadow: 0 4px 4px rgba(0, 0, 0, 0.25);
}

.vch-promo-table {
	margin-top: 3em;
	line-height: 1.5;
}
.vch-promo-table td {
	padding-bottom: 3rem;
}
.vch-promo-table tr:first-child td {
	padding-bottom: 0;
}
.vch-promo-table tr:last-child {
	text-align: center;
}
.vch-promo-table img {
	max-width: 100%;
	box-shadow: 0 4px 4px rgba(0, 0, 0, 0.25);
}
.vch-promo-table h2 {
	position: relative;
	display: inline-block;
}
.vch-promo-table h2 img {
	box-shadow: none;
	width: 2rem;
	height: 2rem;
	position: absolute;
	left: calc(100% + 1rem);
	top: 50%;
	transform: translateY(-50%);
}
.vch-promo-feat-text {
	font-size: 110%;
	vertical-align: middle;
}
.vch-promo-feat-text:first-child {
	padding-right: 4rem;
}
.vch-promo-feat-text:last-child {
	padding-left: 2rem;
}
.vch-promo-table .vch-promo-btn {
	width: 100%;
	margin: 0;
	padding: 1rem 0;
}

</style>

<?php
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
