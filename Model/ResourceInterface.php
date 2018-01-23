<?php
namespace Devture\Bundle\TranslationBundle\Model;

interface ResourceInterface {

	public function getName(): string;

	public function getPath(): string;

	public function getHashPath(): ?string;

	public function getLocaleKey(): string;

	public function isSource(): bool;

	public function setTranslationPack(TranslationPack $pack);

	public function getTranslationPack(): TranslationPack;

}