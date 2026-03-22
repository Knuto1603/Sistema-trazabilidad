<?php

namespace App\apps\security\Service\User;

use App\apps\security\Entity\User;
use App\apps\security\Repository\UserRepository;
use CarlosChininin\AttachFile\Model\AttachFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class UpdatePhotoUserService
{
    public function __construct(
        private UserRepository $userRepository,
        private GetUserService $getUser,
    ) {
    }

    public function execute(string $id, ?UploadedFile $photo): User
    {
        $user = $this->getUser->execute($id, true);
        $file = null;

        if (null !== $photo) {
            $file = $user->getPhoto() ?? new AttachFile();
            $file->setFolder('/user-photo');
            $file->setFile($photo);
        }

        $user->setPhoto($file);
        $this->userRepository->save($user);

        return $user;
    }
}
