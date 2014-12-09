<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Mount;

use \OC\Files\Filesystem;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;

class Manager implements IMountManager {
	/**
	 * @var MountPoint[]
	 */
	private $mounts = array();

	private function formatMountPoint($mountPoint) {
		$mountPoint = Filesystem::normalizePath($mountPoint);
		if (strlen($mountPoint) > 1) {
			$mountPoint .= '/';
		}
		return $mountPoint;
	}

	/**
	 * @param IMountPoint $mount
	 */
	public function addMount(IMountPoint $mount) {
		$this->mounts[$mount->getMountPoint()] = $mount;
	}

	/**
	 * @param string $mountPoint
	 */
	public function removeMount($mountPoint) {
		$mountPoint = $this->formatMountPoint($mountPoint);
		unset($this->mounts[$mountPoint]);
	}

	/**
	 * @param string $mountPoint
	 * @param string $target
	 */
	public function moveMount($mountPoint, $target){
		$mountPoint = $this->formatMountPoint($mountPoint);
		$target = $this->formatMountPoint($target);
		$this->mounts[$target] = $this->mounts[$mountPoint];
		unset($this->mounts[$mountPoint]);
	}

	/**
	 * Find the mount for $path
	 *
	 * @param string $path
	 * @return MountPoint
	 */
	public function find($path) {
		\OC_Util::setupFS();
		$path = $this->formatPath($path);
		if (isset($this->mounts[$path])) {
			return $this->mounts[$path];
		}

		\OC_Hook::emit('OC_Filesystem', 'get_mountpoint', array('path' => $path));
		$foundMountPoint = '';
		$mountPoints = array_keys($this->mounts);
		foreach ($mountPoints as $mountpoint) {
			if (strpos($path, $mountpoint) === 0 and strlen($mountpoint) > strlen($foundMountPoint)) {
				$foundMountPoint = $mountpoint;
			}
		}
		if (isset($this->mounts[$foundMountPoint])) {
			return $this->mounts[$foundMountPoint];
		} else {
			return null;
		}
	}

	/**
	 * Find all mounts in $path
	 *
	 * @param string $path
	 * @return MountPoint[]
	 */
	public function findIn($path) {
		\OC_Util::setupFS();
		$path = $this->formatPath($path);
		$result = array();
		$pathLength = strlen($path);
		$mountPoints = array_keys($this->mounts);
		foreach ($mountPoints as $mountPoint) {
			if (substr($mountPoint, 0, $pathLength) === $path and strlen($mountPoint) > $pathLength) {
				$result[] = $this->mounts[$mountPoint];
			}
		}
		return $result;
	}

	public function clear() {
		$this->mounts = array();
	}

	/**
	 * Find mounts by storage id
	 *
	 * @param string $id
	 * @return MountPoint[]
	 */
	public function findByStorageId($id) {
		\OC_Util::setupFS();
		if (strlen($id) > 64) {
			$id = md5($id);
		}
		$result = array();
		foreach ($this->mounts as $mount) {
			if ($mount->getStorageId() === $id) {
				$result[] = $mount;
			}
		}
		return $result;
	}

	/**
	 * @return MountPoint[]
	 */
	public function getAll() {
		return $this->mounts;
	}

	/**
	 * Find mounts by numeric storage id
	 *
	 * @param int $id
	 * @return MountPoint[]
	 */
	public function findByNumericId($id) {
		$storageId = \OC\Files\Cache\Storage::getStorageId($id);
		return $this->findByStorageId($storageId);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function formatPath($path) {
		$path = Filesystem::normalizePath($path);
		if (strlen($path) > 1) {
			$path .= '/';
		}
		return $path;
	}
}
